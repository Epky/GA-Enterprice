<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Brand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the products for this brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the active products for this brand.
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('status', 'active');
    }

    /**
     * Scope a query to only include active brands.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to search brands by name or description.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Check if the brand has any products.
     */
    public function getHasProductsAttribute(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Get the display logo for this brand.
     * Returns the brand's logo if available, otherwise returns the first product image.
     */
    public function getDisplayLogoAttribute(): ?string
    {
        // First check if brand has its own logo
        if ($this->logo_url) {
            return $this->logo_url;
        }

        // Otherwise, get the first product image from active products
        $product = $this->products()
            ->where('status', 'active')
            ->whereHas('images')
            ->with('primaryImage')
            ->first();

        return $product?->primaryImage?->image_url ?? $product?->images?->first()?->image_url;
    }
}