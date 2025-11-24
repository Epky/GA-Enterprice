<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'variant_type',
        'variant_value',
        'price_adjustment',
        'image_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the inventory records for this variant.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Scope a query to only include active variants.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by variant type.
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('variant_type', $type);
    }

    /**
     * Scope a query to search variants by name, SKU, or variant value.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('variant_value', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Get the effective price for this variant.
     */
    public function getEffectivePriceAttribute(): float
    {
        $basePrice = $this->product->sale_price ?? $this->product->base_price;
        return $basePrice + $this->price_adjustment;
    }

    /**
     * Get the full name including product name and variant details.
     */
    public function getFullNameAttribute(): string
    {
        return $this->product->name . ' - ' . $this->name;
    }

    /**
     * Get the display name for the variant (variant type and value).
     */
    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->variant_type) . ': ' . $this->variant_value;
    }

    /**
     * Get the total stock quantity for this variant.
     */
    public function getTotalStockAttribute(): int
    {
        return $this->inventory->sum('quantity_available');
    }

    /**
     * Check if the variant is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    /**
     * Get the image URL for this variant (fallback to product primary image).
     */
    public function getImageUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        // Fallback to product's primary image
        return $this->product->primaryImage?->image_url;
    }

    /**
     * Get the full URL for the variant image.
     */
    public function getFullImageUrlAttribute(): ?string
    {
        $imageUrl = $this->image_url;
        
        if (!$imageUrl) {
            return null;
        }

        // If the image_url is already a full URL, return it as is
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }

        // Otherwise, construct the full URL
        return config('app.url') . '/storage/' . $imageUrl;
    }
}