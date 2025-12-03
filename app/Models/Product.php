<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sku',
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'brand_id',
        'base_price',
        'sale_price',
        'cost_price',
        'is_featured',
        'is_new_arrival',
        'is_best_seller',
        'status',
        'average_rating',
        'review_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_best_seller' => 'boolean',
        'average_rating' => 'decimal:2',
        'review_count' => 'integer',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the active variants for the product.
     */
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    /**
     * Get the specifications for the product.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('display_order');
    }

    /**
     * Get the inventory records for the product.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include new arrival products.
     */
    public function scopeNewArrivals(Builder $query): void
    {
        $query->where('is_new_arrival', true);
    }

    /**
     * Scope a query to only include best seller products.
     */
    public function scopeBestSellers(Builder $query): void
    {
        $query->where('is_best_seller', true);
    }

    /**
     * Scope a query to only include products with low stock.
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereHas('inventory', function (Builder $query) {
            $query->whereRaw('quantity_available <= reorder_level');
        });
    }

    /**
     * Scope a query to search products by name, SKU, or description.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get the price (alias for base_price for backward compatibility).
     */
    public function getPriceAttribute(): float
    {
        return $this->base_price;
    }

    /**
     * Get the effective price (sale price if available, otherwise base price).
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->base_price;
    }

    /**
     * Check if the product is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }

    /**
     * Get the discount percentage if on sale.
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->is_on_sale) {
            return null;
        }

        return round((($this->base_price - $this->sale_price) / $this->base_price) * 100, 2);
    }

    /**
     * Get the total stock quantity across all inventory records.
     * Includes both available and reserved quantities.
     */
    public function getTotalStockAttribute(): int
    {
        return $this->inventory->sum(function ($inventory) {
            return $inventory->quantity_available + $inventory->quantity_reserved;
        });
    }

    /**
     * Get the available stock quantity across all inventory records.
     * Only includes quantity_available (not reserved).
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->inventory->sum('quantity_available');
    }

    /**
     * Check if the product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }
}