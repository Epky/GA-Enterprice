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