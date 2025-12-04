<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffBrandController extends Controller
{

    /**
     * Display a listing of brands with search and filtering
     * Requirement 4.2: Validate brand information and create brand record
     */
    public function index(Request $request)
    {
        $query = Brand::withCount(['products', 'activeProducts'])
            ->with([
                'products' => function($q) {
                    $q->where('status', 'active')
                      ->whereHas('images')
                      ->with('primaryImage:id,product_id,image_url,is_primary')
                      ->limit(1);
                }
            ]);
        
        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter by product availability
        if ($request->filled('has_products')) {
            if ($request->has_products === '1') {
                $query->has('products');
            } elseif ($request->has_products === '0') {
                $query->doesntHave('products');
            }
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'product_count':
                $query->orderBy('products_count', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('name', 'asc');
        }
        
        $brands = $query->paginate(20);
        
        return view('staff.brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new brand
     */
    public function create()
    {
        return view('staff.brands.create');
    }

    /**
     * Store a newly created brand
     * Requirement 4.2: Validate brand information and create brand record
     */
    public function store(BrandRequest $request)
    {

        try {
            DB::beginTransaction();
            
            // Generate unique slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Brand::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            $brand = Brand::create($validated);
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            DB::commit();
            
            return redirect()->route('staff.brands.index')
                ->with('success', 'Brand created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Brand creation failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create brand. Please try again.');
        }
    }

    /**
     * Display the specified brand
     */
    public function show(Brand $brand)
    {
        $brand->load([
            'products.category',
            'products.inventory',
            'products.primaryImage'
        ]);
        
        // Get brand statistics
        $stats = [
            'total_products' => $brand->products_count ?? 0,
            'active_products' => $brand->active_products_count ?? 0,
            'inactive_products' => ($brand->products_count ?? 0) - ($brand->active_products_count ?? 0),
            'total_inventory_value' => $brand->products->sum(function($product) {
                return ($product->inventory->quantity_available ?? 0) * ($product->base_price ?? 0);
            }),
            'low_stock_products' => $brand->products->filter(function($product) {
                return $product->inventory && $product->inventory->quantity_available <= $product->inventory->reorder_level;
            })->count()
        ];
        
        // Get recent products
        $recentProducts = $brand->products()
            ->with(['category', 'inventory', 'primaryImage'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('staff.brands.show', compact('brand', 'stats', 'recentProducts'));
    }

    /**
     * Show the form for editing the specified brand
     */
    public function edit(Brand $brand)
    {
        return view('staff.brands.edit', compact('brand'));
    }

    /**
     * Update the specified brand
     * Requirement 4.5: Allow updating brand information including logos and descriptions
     */
    public function update(BrandRequest $request, Brand $brand)
    {

        try {
            DB::beginTransaction();
            
            // Handle deactivation of brand with products
            $productCount = $brand->products_count ?? $brand->products()->count();
            if (!$request->boolean('is_active') && $brand->is_active && $productCount > 0) {
                if (!$request->boolean('confirm_deactivation')) {
                    return redirect()->back()
                        ->withInput()
                        ->with('warning', 'This brand has ' . $productCount . ' products. Please confirm deactivation.')
                        ->with('require_confirmation', true);
                }
            }
            
            // Update slug if name changed
            $slug = $brand->slug;
            if ($request->name !== $brand->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            $brand->update($validated);
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            DB::commit();
            
            return redirect()->route('staff.brands.show', $brand)
                ->with('success', 'Brand updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Brand update failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update brand. Please try again.');
        }
    }

    /**
     * Remove the specified brand
     */
    public function destroy(Brand $brand)
    {
        try {
            DB::beginTransaction();
            
            // Check if brand has products
            $productCount = $brand->products_count ?? $brand->products()->count();
            if ($productCount > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete brand with products. Please move or delete products first.');
            }
            
            $brand->delete();
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            DB::commit();
            
            return redirect()->route('staff.brands.index')
                ->with('success', 'Brand deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Brand deletion failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to delete brand. Please try again.');
        }
    }

    /**
     * Toggle brand active status
     * Requirement 4.5: Add brand activation/deactivation functionality
     */
    public function toggleStatus(Brand $brand)
    {
        try {
            // Check if deactivating brand with products
            $productCount = $brand->products_count ?? $brand->products()->count();
            if ($brand->is_active && $productCount > 0) {
                return redirect()->back()
                    ->with('warning', 'Brand has ' . $productCount . ' products. Deactivating will hide them from customers.');
            }
            
            $brand->update(['is_active' => !$brand->is_active]);
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            $status = $brand->is_active ? 'activated' : 'deactivated';
            
            return redirect()->back()
                ->with('success', "Brand {$status} successfully.");
                
        } catch (\Exception $e) {
            Log::error('Brand status toggle failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to update brand status. Please try again.');
        }
    }

    /**
     * Bulk operations on brands
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'brand_ids' => 'required|array|min:1',
            'brand_ids.*' => 'exists:brands,id'
        ]);

        try {
            DB::beginTransaction();
            
            $brands = Brand::whereIn('id', $request->brand_ids)->get();
            $successCount = 0;
            $errors = [];
            
            foreach ($brands as $brand) {
                try {
                    $productCount = $brand->products_count ?? $brand->products()->count();
                    switch ($request->action) {
                        case 'activate':
                            $brand->update(['is_active' => true]);
                            $successCount++;
                            break;
                            
                        case 'deactivate':
                            if ($productCount > 0) {
                                $errors[] = "Brand '{$brand->name}' has products and cannot be deactivated.";
                            } else {
                                $brand->update(['is_active' => false]);
                                $successCount++;
                            }
                            break;
                            
                        case 'delete':
                            if ($productCount > 0) {
                                $errors[] = "Brand '{$brand->name}' has products and cannot be deleted.";
                            } else {
                                $brand->delete();
                                $successCount++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to {$request->action} brand '{$brand->name}': " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "Successfully processed {$successCount} brands.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk brand action failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Bulk operation failed. Please try again.');
        }
    }

    /**
     * Get brand data for AJAX requests
     */
    public function getData(Request $request)
    {
        $query = Brand::withCount(['products', 'activeProducts']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        $brands = $query->orderBy('name')->paginate(10);
        
        return response()->json([
            'brands' => $brands->items(),
            'pagination' => [
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'total' => $brands->total()
            ]
        ]);
    }

    /**
     * Get brand statistics for dashboard
     * Cached for 5 minutes to improve performance
     */
    public function getStats()
    {
        $stats = Cache::remember('brands.stats', 300, function () {
            return [
                'total_brands' => Brand::count(),
                'active_brands' => Brand::active()->count(),
                'inactive_brands' => Brand::where('is_active', false)->count(),
                'brands_with_products' => Brand::has('products')->count(),
                'brands_without_products' => Brand::doesntHave('products')->count(),
                'top_brands_by_products' => Brand::withCount('products')
                    ->orderBy('products_count', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($brand) {
                        return [
                            'name' => $brand->name,
                            'product_count' => $brand->products_count,
                            'is_active' => $brand->is_active
                        ];
                    })
            ];
        });
        
        return response()->json($stats);
    }

    /**
     * Export brands data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        
        $query = Brand::withCount(['products', 'activeProducts']);
        
        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        if ($request->filled('has_products')) {
            if ($request->has_products === '1') {
                $query->has('products');
            } elseif ($request->has_products === '0') {
                $query->doesntHave('products');
            }
        }
        
        $brands = $query->orderBy('name')->get();
        
        // For now, return JSON response - can be extended to support CSV/Excel
        return response()->json([
            'data' => $brands->map(function($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'logo_url' => $brand->logo_url,
                    'website_url' => $brand->website_url,
                    'is_active' => $brand->is_active,
                    'product_count' => $brand->products_count,
                    'active_product_count' => $brand->active_products_count,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at
                ];
            }),
            'total' => $brands->count(),
            'exported_at' => now(),
            'filters' => $request->only(['status', 'has_products'])
        ]);
    }

    /**
     * Duplicate a brand
     */
    public function duplicate(Brand $brand)
    {
        try {
            DB::beginTransaction();
            
            // Generate unique name and slug for duplicate
            $baseName = $brand->name . ' (Copy)';
            $name = $baseName;
            $counter = 1;
            
            while (Brand::where('name', $name)->exists()) {
                $name = $baseName . ' ' . $counter;
                $counter++;
            }
            
            $slug = Str::slug($name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Brand::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $duplicatedBrand = Brand::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $brand->description,
                'logo_url' => $brand->logo_url,
                'website_url' => $brand->website_url,
                'is_active' => false // Start as inactive for review
            ]);
            
            DB::commit();
            
            return redirect()->route('staff.brands.edit', $duplicatedBrand)
                ->with('success', 'Brand duplicated successfully. Please review and update the details.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Brand duplication failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to duplicate brand. Please try again.');
        }
    }

    /**
     * Search brands for autocomplete/select inputs
     * Uses cached active brands list for better performance
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);
        
        // Get cached active brands
        $allBrands = Cache::remember('brands.active', 3600, function () {
            return Brand::active()
                ->select('id', 'name', 'slug', 'logo_url')
                ->orderBy('name')
                ->get();
        });
        
        // Filter in memory for better performance
        $brands = $allBrands->filter(function($brand) use ($query) {
            return stripos($brand->name, $query) !== false;
        })->take($limit);
        
        return response()->json([
            'results' => $brands->map(function($brand) {
                return [
                    'id' => $brand->id,
                    'text' => $brand->name,
                    'slug' => $brand->slug,
                    'logo_url' => $brand->logo_url
                ];
            })
        ]);
    }

    /**
     * Store a newly created brand via AJAX (inline creation)
     * Requirements: 2.2, 2.3, 3.1, 3.2, 3.5
     */
    public function storeInline(BrandRequest $request)
    {
        // Log the incoming request for debugging
        Log::info('Inline brand creation attempt', [
            'user_id' => auth()->id(),
            'is_ajax' => $request->ajax(),
            'data' => $request->all()
        ]);

        // Ensure this is an AJAX request
        if (!$request->ajax()) {
            Log::warning('Non-AJAX request to inline endpoint');
            return response()->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            // Generate unique slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Brand::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            $brand = Brand::create($validated);
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            DB::commit();
            
            Log::info('Inline brand created successfully', ['brand_id' => $brand->id]);
            
            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'is_active' => $brand->is_active
                ],
                'message' => 'Brand created successfully.'
            ];
            
            Log::info('Sending response', ['response' => $responseData]);
            
            return response()->json($responseData, 200, [
                'Content-Type' => 'application/json'
            ]);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Inline brand validation failed', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed.'
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Inline brand creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete a brand via AJAX (inline deletion)
     * Requirements: 2.2, 2.3, 2.4, 2.5
     */
    public function deleteInline(Brand $brand)
    {
        // Log the deletion attempt
        $productCount = $brand->products_count ?? $brand->products()->count();
        Log::info('Inline brand deletion attempt', [
            'user_id' => auth()->id(),
            'brand_id' => $brand->id,
            'brand_name' => $brand->name,
            'product_count' => $productCount
        ]);

        try {
            DB::beginTransaction();
            
            // Check if brand has associated products (Requirement 2.5)
            if ($productCount > 0) {
                Log::warning('Brand deletion prevented - has products', [
                    'brand_id' => $brand->id,
                    'product_count' => $productCount
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete brand with {$productCount} associated products.",
                    'product_count' => $productCount
                ], 422);
            }
            
            // Delete the brand (Requirement 2.3)
            $brandName = $brand->name;
            $brand->delete();
            
            // Clear brand caches
            $this->clearBrandCaches();
            
            DB::commit();
            
            Log::info('Brand deleted successfully via inline', [
                'brand_name' => $brandName
            ]);
            
            // Return success response (Requirement 2.4)
            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.'
            ]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Inline brand deletion failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active brands for dropdown refresh
     * Requirements: 3.1, 3.2, 3.5
     */
    public function getActive()
    {
        try {
            // Get cached active brands
            $brands = Cache::remember('brands.active', 3600, function () {
                return Brand::active()
                    ->select('id', 'name', 'slug')
                    ->orderBy('name')
                    ->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $brands->map(function($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch active brands: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands.'
            ], 500);
        }
    }

    /**
     * Clear all brand-related caches
     */
    private function clearBrandCaches(): void
    {
        Cache::forget('brands.active');
        Cache::forget('brands.stats');
        
        // Only use cache tags if the driver supports it (Redis, Memcached)
        // Database and file drivers don't support tagging
        try {
            if (in_array(config('cache.default'), ['redis', 'memcached'])) {
                Cache::tags(['brands'])->flush();
            }
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, skip it
            Log::debug('Cache tagging not supported by current driver');
        }
    }
}
