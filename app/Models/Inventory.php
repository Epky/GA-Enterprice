<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'location',
        'quantity_available',
        'quantity_reserved',
        'quantity_sold',
        'reorder_level',
        'reorder_quantity',
        'last_restocked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_sold' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'last_restocked_at' => 'datetime',
    ];

    /**
     * Get the product that owns the inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the inventory (if applicable).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the inventory movements for this inventory record.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id', 'product_id')
                    ->where(function ($query) {
                        $query->where('variant_id', $this->variant_id)
                              ->orWhereNull('variant_id');
                    });
    }

    /**
     * Scope a query to only include low stock items.
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereRaw('quantity_available <= reorder_level');
    }

    /**
     * Scope a query to only include out of stock items.
     */
    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('quantity_available', '<=', 0);
    }

    /**
     * Scope a query to only include in stock items.
     */
    public function scopeInStock(Builder $query): void
    {
        $query->where('quantity_available', '>', 0);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeAtLocation(Builder $query, string $location): void
    {
        $query->where('location', $location);
    }

    /**
     * Get the total quantity (available + reserved).
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_available + $this->quantity_reserved;
    }

    /**
     * Check if the inventory is low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_available <= $this->reorder_level;
    }

    /**
     * Check if the inventory is out of stock.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_available <= 0;
    }

    /**
     * Get the stock status as a string.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        }

        if ($this->is_low_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get the stock status color for UI display.
     */
    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get the quantity needed to reach reorder level.
     */
    public function getQuantityNeededAttribute(): int
    {
        $needed = $this->reorder_level - $this->quantity_available;
        return max(0, $needed);
    }

    /**
     * Get the suggested restock quantity.
     */
    public function getSuggestedRestockQuantityAttribute(): int
    {
        if (!$this->is_low_stock) {
            return 0;
        }

        return max($this->quantity_needed, $this->reorder_quantity);
    }

    /**
     * Get the display name for this inventory record.
     */
    public function getDisplayNameAttribute(): string
    {
        // Handle orphaned inventory records (product was deleted)
        if (!$this->product) {
            return 'Unknown Product (ID: ' . $this->product_id . ')';
        }
        
        $name = $this->product->name;
        
        if ($this->variant) {
            $name .= ' - ' . $this->variant->name;
        }

        if ($this->location !== 'main_warehouse') {
            $name .= ' (' . ucwords(str_replace('_', ' ', $this->location)) . ')';
        }

        return $name;
    }

    /**
     * Adjust the inventory quantity and create a movement record.
     */
    public function adjustQuantity(int $quantity, string $movementType, ?string $notes = null, ?int $performedBy = null): bool
    {
        $oldQuantity = $this->quantity_available;
        $newQuantity = $oldQuantity + $quantity;

        // Prevent negative inventory
        if ($newQuantity < 0) {
            return false;
        }

        // Update the inventory
        $this->quantity_available = $newQuantity;
        
        if ($movementType === 'purchase' || $movementType === 'adjustment') {
            $this->last_restocked_at = now();
        }

        $this->save();

        // Create movement record
        InventoryMovement::create([
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'location_to' => $this->location,
            'notes' => $notes,
            'performed_by' => $performedBy,
        ]);

        return true;
    }

    /**
     * Reserve quantity for an order.
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->quantity_available -= $quantity;
        $this->quantity_reserved += $quantity;
        
        return $this->save();
    }

    /**
     * Release reserved quantity.
     */
    public function releaseReservedQuantity(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_available += $quantity;
        
        return $this->save();
    }

    /**
     * Fulfill reserved quantity (convert reserved to sold).
     */
    public function fulfillReservedQuantity(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_sold += $quantity;
        
        return $this->save();
    }
}