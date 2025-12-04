<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
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
        'parent_id',
        'image_url',
        'display_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get all active child categories.
     */
    public function activeChildren(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('display_order');
    }

    /**
     * Get the products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the active products in this category.
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('status', 'active');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories (no parent).
     */
    public function scopeRoot(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include child categories (has parent).
     */
    public function scopeChildren(Builder $query): void
    {
        $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to order categories by display order.
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Get all ancestors of this category.
     */
    public function getAncestorsAttribute(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get the full path of this category (including ancestors).
     */
    public function getFullPathAttribute(): string
    {
        $path = collect($this->ancestors)->pluck('name')->toArray();
        $path[] = $this->name;

        return implode(' > ', $path);
    }

    /**
     * Check if this category has children.
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the depth level of this category in the hierarchy.
     */
    public function getDepthAttribute(): int
    {
        return count($this->ancestors);
    }

    /**
     * Get all descendant categories (children, grandchildren, etc.).
     */
    public function getAllDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get the product count for this category (including descendants).
     */
    public function getTotalProductCountAttribute(): int
    {
        $count = $this->products()->count();
        
        foreach ($this->children as $child) {
            $count += $child->total_product_count;
        }

        return $count;
    }

    /**
     * Get the first product image from products in this category.
     * This automatically displays a category image based on its products.
     */
    public function getDisplayImageAttribute(): ?string
    {
        // First check if category has its own image
        if ($this->image_url) {
            return $this->image_url;
        }

        // Otherwise, get the first product image from active products
        $product = $this->products()
            ->where('status', 'active')
            ->whereHas('images')
            ->with('primaryImage')
            ->first();

        return $product?->primaryImage?->image_url ?? $product?->images?->first()?->image_url;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}