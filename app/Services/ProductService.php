<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Get paginated products with optional filters.
     * Optimized with eager loading to prevent N+1 queries.
     */
    public function getProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Eager load relationships to prevent N+1 queries
        $query = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,image_url,alt_text',
            'inventory:id,product_id,quantity_available,reorder_level'
        ]);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply featured filter
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        // Apply low stock filter
        if (!empty($filters['low_stock'])) {
            $query->lowStock();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Create a new product.
     * Clears relevant caches after creation.
     */
    public function createProduct(array $data, $request = null): Product
    {
        return DB::transaction(function () use ($data, $request) {
            // ALWAYS generate a unique slug from the name to prevent conflicts
            // Even if slug is provided, we ensure it's unique
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            // Validate business rules (SKU uniqueness, etc.)
            $this->validateProductData($data);

            $product = Product::create($data);

            // Create default inventory record if stock data is provided
            if (isset($data['initial_stock'])) {
                $product->inventory()->create([
                    'quantity_available' => $data['initial_stock'],
                    'quantity_reserved' => 0,
                    'quantity_sold' => 0,
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'reorder_quantity' => $data['reorder_quantity'] ?? 50,
                    'location' => $data['location'] ?? 'main',
                ]);
            }

            // Handle image uploads if provided
            if ($request && $request->hasFile('images')) {
                $imageUploadService = app(ImageUploadService::class);
                $imageUploadService->uploadProductImages(
                    $product,
                    $request->file('images'),
                    [
                        'alt_texts' => $request->input('alt_texts', [])
                    ]
                );
            }

            // Clear relevant caches
            $this->clearProductCaches();

            return $product->load(['category', 'brand', 'inventory', 'images']);
        });
    }

    /**
     * Update an existing product.
     * Clears relevant caches after update.
     */
    public function updateProduct(Product $product, array $data, $request = null): Product
    {
        return DB::transaction(function () use ($product, $data, $request) {
            // Generate new slug if name changed
            if (isset($data['name']) && $data['name'] !== $product->name) {
                if (empty($data['slug'])) {
                    $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
                }
            }

            // Validate business rules
            $this->validateProductData($data, $product->id);

            $product->update($data);

            // Handle image uploads if provided
            if ($request && $request->hasFile('images')) {
                $imageUploadService = app(ImageUploadService::class);
                $imageUploadService->uploadProductImages(
                    $product,
                    $request->file('images'),
                    [
                        'alt_texts' => $request->input('alt_texts', [])
                    ]
                );
            }

            // Clear relevant caches
            $this->clearProductCaches();

            return $product->load(['category', 'brand', 'inventory', 'images']);
        });
    }

    /**
     * Delete a product and handle related data.
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Check if product has any orders or sales history
            // This would prevent deletion if there are dependencies
            
            // Delete related images from storage (handled by ImageUploadService)
            foreach ($product->images as $image) {
                // Image deletion will be handled by ImageUploadService
            }

            // Delete the product (cascade will handle related records)
            $deleted = $product->delete();
            
            // Clear relevant caches
            $this->clearProductCaches();
            
            return $deleted;
        });
    }

    /**
     * Change product status.
     * Clears relevant caches after status change.
     */
    public function changeProductStatus(Product $product, string $status): Product
    {
        $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
        
        if (!in_array($status, $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
            ]);
        }

        $product->update(['status' => $status]);
        
        // Clear relevant caches
        $this->clearProductCaches();

        return $product;
    }

    /**
     * Toggle featured status of a product.
     * Clears featured products cache.
     */
    public function toggleFeatured(Product $product): Product
    {
        $product->update(['is_featured' => !$product->is_featured]);
        
        // Clear featured products cache
        Cache::forget('products.featured');
        
        // Only use cache tags if the driver supports it (Redis, Memcached)
        try {
            if (in_array(config('cache.default'), ['redis', 'memcached'])) {
                Cache::tags(['products.featured'])->flush();
            }
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, skip it
        }
        
        $this->clearProductCaches();

        return $product;
    }

    /**
     * Bulk update product statuses.
     * Clears relevant caches after bulk update.
     */
    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
        
        if (!in_array($status, $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
            ]);
        }

        $updated = Product::whereIn('id', $productIds)->update(['status' => $status]);
        
        // Clear relevant caches
        $this->clearProductCaches();
        
        return $updated;
    }

    /**
     * Get products with low stock.
     * Cached for 5 minutes to reduce database load.
     */
    public function getLowStockProducts(): Collection
    {
        return Cache::remember('products.low_stock', 300, function () {
            return Product::with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'inventory:id,product_id,quantity_available,reorder_level'
            ])
            ->lowStock()
            ->active()
            ->get();
        });
    }

    /**
     * Get featured products.
     * Cached for 10 minutes to improve performance.
     */
    public function getFeaturedProducts(?int $limit = null): Collection
    {
        $cacheKey = 'products.featured' . ($limit ? ".limit_{$limit}" : '');
        
        return Cache::remember($cacheKey, 600, function () use ($limit) {
            $query = Product::with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'primaryImage:id,product_id,image_url,alt_text'
            ])
            ->featured()
            ->active();

            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        });
    }

    /**
     * Search products by various criteria.
     * Optimized with selective eager loading.
     */
    public function searchProducts(string $query, array $filters = []): Collection
    {
        $searchQuery = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,image_url,alt_text'
        ])
        ->search($query);

        // Apply additional filters
        if (!empty($filters['category_id'])) {
            $searchQuery->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $searchQuery->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['status'])) {
            $searchQuery->where('status', $filters['status']);
        }

        return $searchQuery->get();
    }

    /**
     * Get product statistics.
     * Cached for 5 minutes to reduce database queries.
     */
    public function getProductStats(): array
    {
        return Cache::remember('products.stats', 300, function () {
            return [
                'total_products' => Product::count(),
                'active_products' => Product::active()->count(),
                'featured_products' => Product::featured()->count(),
                'low_stock_products' => Product::lowStock()->count(),
                'out_of_stock_products' => Product::where('status', 'out_of_stock')->count(),
            ];
        });
    }

    /**
     * Validate product data according to business rules.
     */
    private function validateProductData(array $data, ?int $excludeId = null): void
    {
        // Check SKU uniqueness
        if (isset($data['sku'])) {
            $query = Product::where('sku', $data['sku']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'sku' => 'The SKU has already been taken.'
                ]);
            }
        }

        // Check slug uniqueness
        if (isset($data['slug'])) {
            $query = Product::where('slug', $data['slug']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'slug' => 'The slug has already been taken.'
                ]);
            }
        }

        // Validate category exists and is active
        if (isset($data['category_id'])) {
            if (!Category::where('id', $data['category_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => 'The selected category is invalid or inactive.'
                ]);
            }
        }

        // Validate brand exists and is active
        if (isset($data['brand_id'])) {
            if (!Brand::where('id', $data['brand_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'brand_id' => 'The selected brand is invalid or inactive.'
                ]);
            }
        }

        // Validate pricing logic
        if (isset($data['sale_price']) && isset($data['base_price'])) {
            if ($data['sale_price'] >= $data['base_price']) {
                throw ValidationException::withMessages([
                    'sale_price' => 'Sale price must be less than base price.'
                ]);
            }
        }

        if (isset($data['cost_price']) && isset($data['base_price'])) {
            if ($data['cost_price'] > $data['base_price']) {
                throw ValidationException::withMessages([
                    'cost_price' => 'Cost price should not exceed base price.'
                ]);
            }
        }
    }

    /**
     * Generate a unique slug for the product.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = Product::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Duplicate a product with all its related data.
     */
    public function duplicateProduct(Product $originalProduct): Product
    {
        DB::beginTransaction();

        try {
            // Load all related data
            $originalProduct->load(['images', 'variants', 'specifications']);

            // Create new product data
            $productData = $originalProduct->toArray();
            
            // Remove ID and timestamps
            unset($productData['id'], $productData['created_at'], $productData['updated_at']);
            
            // Modify name and SKU to indicate it's a copy
            $productData['name'] = $productData['name'] . ' (Copy)';
            $productData['sku'] = $this->generateUniqueSku($productData['sku'] . '-copy');
            $productData['slug'] = $this->generateUniqueSlug($productData['name']);
            
            // Set as inactive by default
            $productData['status'] = 'inactive';
            $productData['is_featured'] = false;

            // Create the new product
            $newProduct = Product::create($productData);

            // Duplicate images
            foreach ($originalProduct->images as $image) {
                $newProduct->images()->create([
                    'image_url' => $image->image_url,
                    'alt_text' => $image->alt_text,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ]);
            }

            // Duplicate variants
            foreach ($originalProduct->variants as $variant) {
                $variantData = $variant->toArray();
                unset($variantData['id'], $variantData['product_id'], $variantData['created_at'], $variantData['updated_at']);
                
                // Generate unique SKU for variant
                $variantData['sku'] = $this->generateUniqueSku($variantData['sku'] . '-copy');
                
                $newProduct->variants()->create($variantData);
            }

            // Duplicate specifications
            foreach ($originalProduct->specifications as $spec) {
                $specData = $spec->toArray();
                unset($specData['id'], $specData['product_id'], $specData['created_at'], $specData['updated_at']);
                
                $newProduct->specifications()->create($specData);
            }

            DB::commit();

            return $newProduct;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique SKU.
     */
    private function generateUniqueSku(string $baseSku): string
    {
        $sku = $baseSku;
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    /**
     * Update pricing for a single product.
     */
    public function updatePricing(Product $product, array $pricingData): Product
    {
        // Validate pricing logic
        if (isset($pricingData['sale_price']) && $pricingData['sale_price'] !== null) {
            $basePrice = $pricingData['base_price'] ?? $product->base_price;
            if ($pricingData['sale_price'] >= $basePrice) {
                throw ValidationException::withMessages([
                    'sale_price' => 'Sale price must be less than base price.'
                ]);
            }
        }

        if (isset($pricingData['cost_price']) && $pricingData['cost_price'] !== null) {
            $basePrice = $pricingData['base_price'] ?? $product->base_price;
            if ($pricingData['cost_price'] > $basePrice) {
                throw ValidationException::withMessages([
                    'cost_price' => 'Cost price should not exceed base price.'
                ]);
            }
        }

        $product->update($pricingData);

        return $product->fresh();
    }

    /**
     * Bulk update pricing for multiple products.
     */
    public function bulkUpdatePricing(array $productIds, array $pricingData): int
    {
        $updateData = [];

        // Determine what pricing fields to update
        if (isset($pricingData['adjustment_type'])) {
            // Calculate new prices based on adjustment type
            $products = Product::whereIn('id', $productIds)->get();
            
            foreach ($products as $product) {
                $newPricing = $this->calculatePriceAdjustment($product, $pricingData);
                $product->update($newPricing);
            }

            return $products->count();
        } else {
            // Direct price update
            if (isset($pricingData['base_price'])) {
                $updateData['base_price'] = $pricingData['base_price'];
            }
            if (isset($pricingData['sale_price'])) {
                $updateData['sale_price'] = $pricingData['sale_price'];
            }
            if (isset($pricingData['cost_price'])) {
                $updateData['cost_price'] = $pricingData['cost_price'];
            }

            return Product::whereIn('id', $productIds)->update($updateData);
        }
    }

    /**
     * Calculate price adjustment based on type and value.
     */
    private function calculatePriceAdjustment(Product $product, array $adjustmentData): array
    {
        $result = [];
        $adjustmentType = $adjustmentData['adjustment_type']; // 'percentage' or 'fixed'
        $adjustmentValue = $adjustmentData['adjustment_value'];
        $applyTo = $adjustmentData['apply_to'] ?? 'base_price'; // 'base_price', 'sale_price', or 'both'

        if ($applyTo === 'base_price' || $applyTo === 'both') {
            if ($adjustmentType === 'percentage') {
                $result['base_price'] = round($product->base_price * (1 + ($adjustmentValue / 100)), 2);
            } else {
                $result['base_price'] = round($product->base_price + $adjustmentValue, 2);
            }
        }

        if (($applyTo === 'sale_price' || $applyTo === 'both') && $product->sale_price !== null) {
            if ($adjustmentType === 'percentage') {
                $result['sale_price'] = round($product->sale_price * (1 + ($adjustmentValue / 100)), 2);
            } else {
                $result['sale_price'] = round($product->sale_price + $adjustmentValue, 2);
            }
        }

        return $result;
    }

    /**
     * Update variant pricing.
     */
    public function updateVariantPricing(int $variantId, array $pricingData): bool
    {
        $variant = \App\Models\ProductVariant::findOrFail($variantId);
        
        return $variant->update([
            'price_adjustment' => $pricingData['price_adjustment'] ?? $variant->price_adjustment,
        ]);
    }

    /**
     * Bulk update variant pricing for a product.
     */
    public function bulkUpdateVariantPricing(Product $product, array $variantPricing): int
    {
        $updated = 0;

        foreach ($variantPricing as $variantId => $pricing) {
            $variant = $product->variants()->find($variantId);
            if ($variant) {
                $variant->update(['price_adjustment' => $pricing['price_adjustment']]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Update product visibility settings.
     */
    public function updateVisibility(Product $product, array $visibilityData): Product
    {
        $allowedFields = ['status', 'is_featured', 'is_new_arrival', 'is_best_seller'];
        $updateData = array_intersect_key($visibilityData, array_flip($allowedFields));

        // Validate status if provided
        if (isset($updateData['status'])) {
            $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
            if (!in_array($updateData['status'], $validStatuses)) {
                throw ValidationException::withMessages([
                    'status' => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
                ]);
            }
        }

        $product->update($updateData);

        return $product->fresh();
    }

    /**
     * Bulk update product visibility settings.
     */
    public function bulkUpdateVisibility(array $productIds, array $visibilityData): int
    {
        $allowedFields = ['status', 'is_featured', 'is_new_arrival', 'is_best_seller'];
        $updateData = array_intersect_key($visibilityData, array_flip($allowedFields));

        // Validate status if provided
        if (isset($updateData['status'])) {
            $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
            if (!in_array($updateData['status'], $validStatuses)) {
                throw ValidationException::withMessages([
                    'status' => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
                ]);
            }
        }

        return Product::whereIn('id', $productIds)->update($updateData);
    }

    /**
     * Toggle product marketing flags (featured, new arrival, best seller).
     */
    public function toggleMarketingFlag(Product $product, string $flag): Product
    {
        $validFlags = ['is_featured', 'is_new_arrival', 'is_best_seller'];
        
        if (!in_array($flag, $validFlags)) {
            throw ValidationException::withMessages([
                'flag' => "Invalid flag. Must be one of: " . implode(', ', $validFlags)
            ]);
        }

        $product->update([$flag => !$product->$flag]);

        return $product->fresh();
    }

    /**
     * Get customer preview data for a product.
     */
    public function getCustomerPreview(Product $product): array
    {
        $product->load(['category', 'brand', 'images', 'variants', 'specifications']);

        return [
            'product' => $product,
            'is_visible' => $product->status === 'active',
            'is_purchasable' => $product->status === 'active' && $product->in_stock,
            'display_badges' => [
                'featured' => $product->is_featured,
                'new_arrival' => $product->is_new_arrival,
                'best_seller' => $product->is_best_seller,
                'on_sale' => $product->is_on_sale,
            ],
            'effective_price' => $product->effective_price,
            'stock_status' => $this->getStockStatusLabel($product),
        ];
    }

    /**
     * Get stock status label for display.
     */
    private function getStockStatusLabel(Product $product): string
    {
        if ($product->status === 'out_of_stock' || !$product->in_stock) {
            return 'Out of Stock';
        }

        $totalStock = $product->total_stock;
        
        if ($totalStock <= 0) {
            return 'Out of Stock';
        } elseif ($totalStock <= 10) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Schedule product activation/deactivation.
     */
    public function scheduleStatusChange(Product $product, string $status, \DateTime $scheduledAt): bool
    {
        $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
        
        if (!in_array($status, $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
            ]);
        }

        // Store scheduled status change (would require a scheduled_status_changes table)
        // For now, we'll just update immediately if the scheduled time is in the past
        if ($scheduledAt <= new \DateTime()) {
            $product->update(['status' => $status]);
            return true;
        }

        // In a full implementation, this would create a scheduled job
        // For now, return false to indicate scheduling is not yet implemented
        return false;
    }

    /**
     * Get products by visibility status.
     * Optimized with selective eager loading.
     */
    public function getProductsByVisibility(array $filters = []): Collection
    {
        $query = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,image_url,alt_text'
        ]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['is_new_arrival'])) {
            $query->where('is_new_arrival', $filters['is_new_arrival']);
        }

        if (isset($filters['is_best_seller'])) {
            $query->where('is_best_seller', $filters['is_best_seller']);
        }

        return $query->get();
    }

    /**
     * Clear all product-related caches.
     */
    private function clearProductCaches(): void
    {
        Cache::forget('products.stats');
        Cache::forget('products.low_stock');
        Cache::forget('products.featured');
        
        // Clear featured products with different limits
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget("products.featured.limit_{$i}");
        }
    }
}
