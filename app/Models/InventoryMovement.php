<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovement extends Model
{
    use HasFactory;

    /**
     * Business movement types (user-initiated inventory changes)
     */
    public const BUSINESS_MOVEMENT_TYPES = [
        'purchase',
        'sale',
        'return',
        'damage',
        'adjustment',
        'transfer',
    ];

    /**
     * System movement types (internal operations)
     */
    public const SYSTEM_MOVEMENT_TYPES = [
        'reservation',
        'release',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'movement_type',
        'quantity',
        'location_from',
        'location_to',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reference_id' => 'integer',
        'performed_by' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'transaction_reference',
        'reason',
        'clean_notes',
    ];

    /**
     * Get the product that owns the movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the movement (if applicable).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the user who performed the movement.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope a query to filter by movement type.
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('movement_type', $type);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to order by most recent first.
     */
    public function scopeRecent(Builder $query): void
    {
        $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeAtLocation(Builder $query, string $location): void
    {
        $query->where(function (Builder $query) use ($location) {
            $query->where('location_from', $location)
                  ->orWhere('location_to', $location);
        });
    }

    /**
     * Get the movement type label for display.
     */
    public function getMovementTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            'purchase' => 'Purchase/Restock',
            'sale' => 'Sale',
            'return' => 'Return',
            'adjustment' => 'Adjustment',
            'damage' => 'Damage/Loss',
            'transfer' => 'Transfer',
            default => ucfirst($this->movement_type),
        };
    }

    /**
     * Get the movement direction (in/out).
     */
    public function getMovementDirectionAttribute(): string
    {
        return match ($this->movement_type) {
            'purchase', 'return', 'adjustment' => $this->quantity > 0 ? 'in' : 'out',
            'sale', 'damage' => 'out',
            'transfer' => 'transfer',
            default => $this->quantity > 0 ? 'in' : 'out',
        };
    }

    /**
     * Get the movement color for UI display.
     */
    public function getMovementColorAttribute(): string
    {
        return match ($this->movement_direction) {
            'in' => 'green',
            'out' => 'red',
            'transfer' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get the absolute quantity (always positive).
     */
    public function getAbsoluteQuantityAttribute(): int
    {
        return abs($this->quantity);
    }

    /**
     * Get the display name for this movement.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->product->name;
        
        if ($this->variant) {
            $name .= ' - ' . $this->variant->name;
        }

        return $name;
    }

    /**
     * Get the location display text.
     */
    public function getLocationDisplayAttribute(): string
    {
        if ($this->movement_type === 'transfer') {
            $from = $this->location_from ? ucwords(str_replace('_', ' ', $this->location_from)) : 'Unknown';
            $to = $this->location_to ? ucwords(str_replace('_', ' ', $this->location_to)) : 'Unknown';
            return "{$from} → {$to}";
        }

        $location = $this->location_to ?? $this->location_from ?? 'Unknown';
        return ucwords(str_replace('_', ' ', $location));
    }

    /**
     * Get the reference display text.
     */
    public function getReferenceDisplayAttribute(): ?string
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        return match ($this->reference_type) {
            'order' => "Order #{$this->reference_id}",
            'purchase_order' => "PO #{$this->reference_id}",
            'return' => "Return #{$this->reference_id}",
            default => ucfirst($this->reference_type) . " #{$this->reference_id}",
        };
    }

    /**
     * Check if this is a business movement
     * 
     * @return bool
     */
    public function isBusinessMovement(): bool
    {
        return in_array($this->movement_type, self::BUSINESS_MOVEMENT_TYPES);
    }

    /**
     * Check if this is a system movement
     * 
     * @return bool
     */
    public function isSystemMovement(): bool
    {
        return in_array($this->movement_type, self::SYSTEM_MOVEMENT_TYPES);
    }

    /**
     * Extract transaction reference from notes
     * 
     * @return array|null ['type' => 'walk-in', 'id' => 'WI-20251125-0001']
     */
    public function getTransactionReferenceAttribute(): ?array
    {
        if (empty($this->notes)) {
            return null;
        }

        // Pattern for walk-in transaction references: WI-YYYYMMDD-NNNN
        if (preg_match('/WI-\d{8}-\d{4}/', $this->notes, $matches)) {
            return [
                'type' => 'walk-in',
                'id' => $matches[0],
            ];
        }

        return null;
    }

    /**
     * Parse reason from notes
     * 
     * @return string|null
     */
    public function getReasonAttribute(): ?string
    {
        if (empty($this->notes)) {
            return null;
        }

        // Pattern for reason: (Reason: text)
        if (preg_match('/\(Reason:\s*([^)]+)\)/', $this->notes, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get notes without structured data
     * 
     * @return string|null
     */
    public function getCleanNotesAttribute(): ?string
    {
        if (empty($this->notes)) {
            return null;
        }

        $cleanNotes = $this->notes;

        // Remove reason pattern: (Reason: text)
        $cleanNotes = preg_replace('/\(Reason:\s*[^)]+\)/', '', $cleanNotes);

        // Remove transaction reference patterns
        $cleanNotes = preg_replace('/Reserved for walk-in transaction:\s*WI-\d{8}-\d{4}/', '', $cleanNotes);
        $cleanNotes = preg_replace('/Walk-in transaction completed:\s*WI-\d{8}-\d{4}/', '', $cleanNotes);
        $cleanNotes = preg_replace('/Released from walk-in transaction:\s*WI-\d{8}-\d{4}/', '', $cleanNotes);

        // Clean up extra whitespace
        $cleanNotes = preg_replace('/\s+/', ' ', $cleanNotes);
        $cleanNotes = trim($cleanNotes);

        return empty($cleanNotes) ? null : $cleanNotes;
    }

    /**
     * Get related movements (same transaction reference)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRelatedMovements(): \Illuminate\Database\Eloquent\Collection
    {
        $transactionRef = $this->transaction_reference;

        if (!$transactionRef) {
            return collect();
        }

        return self::where('id', '!=', $this->id)
            ->where('product_id', $this->product_id)
            ->where('notes', 'like', '%' . $transactionRef['id'] . '%')
            ->get();
    }

    /**
     * Get the color class for quantity display
     * 
     * @return string
     */
    public function getQuantityColorClass(): string
    {
        if ($this->quantity > 0) {
            return 'text-green-600';
        } elseif ($this->quantity < 0) {
            return 'text-red-600';
        } else {
            return 'text-gray-600';
        }
    }

    /**
     * Get the badge color class for movement type
     * 
     * @return string
     */
    public function getTypeBadgeColor(): string
    {
        return match ($this->movement_type) {
            'purchase' => 'bg-blue-100',
            'sale' => 'bg-green-100',
            'return' => 'bg-yellow-100',
            'damage' => 'bg-red-100',
            'adjustment' => 'bg-purple-100',
            'transfer' => 'bg-indigo-100',
            'reservation' => 'bg-orange-100',
            'release' => 'bg-gray-100',
            default => 'bg-gray-100',
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set created_at timestamp when creating
        static::creating(function ($movement) {
            if (!$movement->created_at) {
                $movement->created_at = now();
            }
        });
    }
}