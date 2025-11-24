<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * Get all active promotions.
     */
    public function getActivePromotions(): Collection
    {
        return Promotion::active()->available()->get();
    }

    /**
     * Get promotions applicable to a specific product.
     */
    public function getPromotionsForProduct(int $productId): Collection
    {
        $promotions = $this->getActivePromotions();

        return $promotions->filter(function ($promotion) use ($productId) {
            return $promotion->appliesToProduct($productId);
        });
    }

    /**
     * Calculate the best promotion for a product.
     */
    public function getBestPromotionForProduct(Product $product): ?Promotion
    {
        $promotions = $this->getPromotionsForProduct($product->id);

        if ($promotions->isEmpty()) {
            return null;
        }

        // Find promotion with highest discount
        return $promotions->sortByDesc(function ($promotion) use ($product) {
            return $promotion->calculateDiscount($product->base_price);
        })->first();
    }

    /**
     * Apply promotion to product and calculate promotional price.
     */
    public function applyPromotionToProduct(Product $product, Promotion $promotion): float
    {
        if (!$promotion->appliesToProduct($product->id)) {
            throw ValidationException::withMessages([
                'promotion' => 'This promotion does not apply to the selected product.'
            ]);
        }

        if (!$promotion->is_currently_active) {
            throw ValidationException::withMessages([
                'promotion' => 'This promotion is not currently active.'
            ]);
        }

        $basePrice = $product->base_price;
        $discount = $promotion->calculateDiscount($basePrice);

        return max(0, $basePrice - $discount);
    }

    /**
     * Create a new promotion.
     */
    public function createPromotion(array $data): Promotion
    {
        // Validate dates
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['end_date'] <= $data['start_date']) {
                throw ValidationException::withMessages([
                    'end_date' => 'End date must be after start date.'
                ]);
            }
        }

        // Validate applicable_ids based on applicable_to
        if (isset($data['applicable_to']) && $data['applicable_to'] !== 'all') {
            if (empty($data['applicable_ids'])) {
                throw ValidationException::withMessages([
                    'applicable_ids' => 'Please select at least one item for this promotion.'
                ]);
            }
        }

        return Promotion::create($data);
    }

    /**
     * Update an existing promotion.
     */
    public function updatePromotion(Promotion $promotion, array $data): Promotion
    {
        // Validate dates
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['end_date'] <= $data['start_date']) {
                throw ValidationException::withMessages([
                    'end_date' => 'End date must be after start date.'
                ]);
            }
        }

        $promotion->update($data);

        return $promotion->fresh();
    }

    /**
     * Delete a promotion.
     */
    public function deletePromotion(Promotion $promotion): bool
    {
        return $promotion->delete();
    }

    /**
     * Toggle promotion active status.
     */
    public function toggleStatus(Promotion $promotion): Promotion
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        return $promotion;
    }

    /**
     * Apply promotion to multiple products.
     */
    public function applyPromotionToProducts(Promotion $promotion, array $productIds): int
    {
        $count = 0;

        foreach ($productIds as $productId) {
            if ($promotion->appliesToProduct($productId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get promotion statistics.
     */
    public function getPromotionStats(Promotion $promotion): array
    {
        $affectedProducts = $promotion->getAffectedProducts();
        
        $totalDiscount = 0;
        foreach ($affectedProducts as $product) {
            $totalDiscount += $promotion->calculateDiscount($product->base_price);
        }

        return [
            'affected_products_count' => $affectedProducts->count(),
            'usage_count' => $promotion->usage_count,
            'usage_remaining' => $promotion->usage_limit ? ($promotion->usage_limit - $promotion->usage_count) : null,
            'average_discount' => $affectedProducts->count() > 0 ? ($totalDiscount / $affectedProducts->count()) : 0,
            'status' => $promotion->status,
        ];
    }

    /**
     * Duplicate a promotion.
     */
    public function duplicatePromotion(Promotion $originalPromotion): Promotion
    {
        $data = $originalPromotion->toArray();
        
        // Remove ID and timestamps
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        // Modify name to indicate it's a copy
        $data['name'] = $data['name'] . ' (Copy)';
        
        // Reset usage count
        $data['usage_count'] = 0;
        
        // Set as inactive by default
        $data['is_active'] = false;

        return Promotion::create($data);
    }

    /**
     * Get upcoming scheduled promotions.
     */
    public function getScheduledPromotions(): Collection
    {
        return Promotion::where('is_active', true)
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get expired promotions.
     */
    public function getExpiredPromotions(): Collection
    {
        return Promotion::where('end_date', '<', now())
            ->orderBy('end_date', 'desc')
            ->get();
    }
}
