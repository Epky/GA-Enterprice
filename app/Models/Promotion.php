<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'promotion_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'applicable_to',
        'applicable_ids',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'applicable_ids' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active promotions.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
              ->where('start_date', '<=', now())
              ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include promotions that haven't reached usage limit.
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->whereNull('usage_limit')
                  ->orWhereRaw('usage_count < usage_limit');
        });
    }

    /**
     * Scope a query to filter by applicable type.
     */
    public function scopeApplicableTo(Builder $query, string $type): void
    {
        $query->where('applicable_to', $type);
    }

    /**
     * Check if promotion is currently active.
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->is_active 
            && $this->start_date <= now() 
            && $this->end_date >= now()
            && ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }

    /**
     * Check if promotion has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Check if promotion is scheduled for future.
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->start_date > now();
    }

    /**
     * Get the status of the promotion.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->is_expired) {
            return 'expired';
        }

        if ($this->is_scheduled) {
            return 'scheduled';
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return 'limit_reached';
        }

        return 'active';
    }

    /**
     * Calculate discount amount for a given price.
     */
    public function calculateDiscount(float $price): float
    {
        $discount = 0;

        switch ($this->promotion_type) {
            case 'percentage':
                $discount = ($price * $this->discount_value) / 100;
                break;
            case 'fixed_amount':
                $discount = $this->discount_value;
                break;
            case 'bogo':
                // Buy one get one - 50% discount
                $discount = $price * 0.5;
                break;
            case 'free_shipping':
                // Handled separately in checkout
                $discount = 0;
                break;
        }

        // Apply max discount limit if set
        if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return round($discount, 2);
    }

    /**
     * Check if promotion applies to a specific product.
     */
    public function appliesToProduct(int $productId): bool
    {
        if ($this->applicable_to === 'all') {
            return true;
        }

        if ($this->applicable_to === 'product' && in_array($productId, $this->applicable_ids ?? [])) {
            return true;
        }

        if ($this->applicable_to === 'category') {
            $product = Product::find($productId);
            return $product && in_array($product->category_id, $this->applicable_ids ?? []);
        }

        if ($this->applicable_to === 'brand') {
            $product = Product::find($productId);
            return $product && in_array($product->brand_id, $this->applicable_ids ?? []);
        }

        return false;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get products affected by this promotion.
     */
    public function getAffectedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->applicable_to === 'all') {
            return Product::active()->get();
        }

        if ($this->applicable_to === 'product') {
            return Product::whereIn('id', $this->applicable_ids ?? [])->get();
        }

        if ($this->applicable_to === 'category') {
            return Product::whereIn('category_id', $this->applicable_ids ?? [])->get();
        }

        if ($this->applicable_to === 'brand') {
            return Product::whereIn('brand_id', $this->applicable_ids ?? [])->get();
        }

        return collect();
    }
}
