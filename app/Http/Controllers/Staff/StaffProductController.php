<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StaffProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of products with search and filtering
     * Optimized with selective eager loading to prevent N+1 queries
     */
    public function index(Request $request)
    {
        // Eager load only necessary fields to reduce memory usage
        $query = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,image_url,alt_text',
            'inventory:id,product_id,quantity_available,reorder_level'
        ]);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($brandQuery) use ($search) {
                      $brandQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category', function($categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        // Filter by brand
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by stock level
        if ($request->filled('stock_filter')) {
            switch ($request->stock_filter) {
                case 'low_stock':
                    $query->whereHas('inventory', function($inventoryQuery) {
                        $inventoryQuery->whereRaw('quantity_available <= reorder_level');
                    });
                    break;
                case 'out_of_stock':
                    $query->whereHas('inventory', function($inventoryQuery) {
                        $inventoryQuery->where('quantity_available', 0);
                    });
                    break;
                case 'in_stock':
                    $query->whereHas('inventory', function($inventoryQuery) {
                        $inventoryQuery->where('quantity_available', '>', 0);
                    });
                    break;
            }
        }
        
        // Filter by featured products
        if ($request->filled('featured') && $request->featured === '1') {
            $query->where('is_featured', true);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'stock':
                $query->leftJoin('inventory', 'products.id', '=', 'inventory.product_id')
                      ->orderBy('inventory.quantity_available', $sortOrder)
                      ->select('products.*');
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }
        
        $products = $query->paginate(20);
        
        // Get filter options with caching
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
        });
        
        $brands = Cache::remember('brands.active', 3600, function () {
            return Brand::where('is_active', true)
                ->select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
        });
        
        return view('staff.products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Show the form for creating a new product
     * Uses cached categories and brands for better performance
     */
    public function create()
    {
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->select('id', 'name', 'slug', 'parent_id')
                ->orderBy('name')
                ->get();
        });
        
        $brands = Cache::remember('brands.active', 3600, function () {
            return Brand::where('is_active', true)
                ->select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
        });
        
        return view('staff.products.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created product
     */
    public function store(\App\Http\Requests\ProductStoreRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->validated(), $request);
            
            return redirect()->route('staff.products.index')
                ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product
     * Optimized with selective eager loading
     */
    public function show(Product $product)
    {
        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug,logo_url',
            'images:id,product_id,image_url,alt_text,is_primary,display_order',
            'variants:id,product_id,sku,name,variant_type,variant_value,price_adjustment,is_active',
            'variants.inventory:id,product_id,product_variant_id,quantity_available,reorder_level',
            'inventory:id,product_id,quantity_available,quantity_reserved,reorder_level',
            'specifications:id,product_id,spec_key,spec_value,display_order'
        ]);
        
        return view('staff.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product
     * Uses cached categories and brands for better performance
     */
    public function edit(Product $product)
    {
        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'images:id,product_id,image_url,alt_text,is_primary,display_order',
            'variants:id,product_id,sku,name,variant_type,variant_value,price_adjustment,is_active',
            'specifications:id,product_id,spec_key,spec_value,display_order'
        ]);
        
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->select('id', 'name', 'slug', 'parent_id')
                ->orderBy('name')
                ->get();
        });
        
        $brands = Cache::remember('brands.active', 3600, function () {
            return Brand::where('is_active', true)
                ->select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
        });
        
        return view('staff.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update the specified product
     */
    public function update(\App\Http\Requests\ProductUpdateRequest $request, Product $product)
    {
        try {
            $updatedProduct = $this->productService->updateProduct($product, $request->validated(), $request);
            
            return redirect()->route('staff.products.show', $updatedProduct)
                ->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        try {
            $this->productService->deleteProduct($product);
            
            return redirect()->route('staff.products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete'
        ]);

        try {
            DB::beginTransaction();
            
            $productIds = $request->products;
            $action = $request->action;
            $updatedCount = 0;
            
            switch ($action) {
                case 'activate':
                    $updatedCount = Product::whereIn('id', $productIds)
                        ->update(['status' => 'active']);
                    break;
                case 'deactivate':
                    $updatedCount = Product::whereIn('id', $productIds)
                        ->update(['status' => 'inactive']);
                    break;
                case 'feature':
                    $updatedCount = Product::whereIn('id', $productIds)
                        ->update(['is_featured' => true]);
                    break;
                case 'unfeature':
                    $updatedCount = Product::whereIn('id', $productIds)
                        ->update(['is_featured' => false]);
                    break;
                case 'delete':
                    $updatedCount = Product::whereIn('id', $productIds)->count();
                    Product::whereIn('id', $productIds)->delete();
                    break;
            }
            
            DB::commit();
            
            return redirect()->back()
                ->with('success', "Successfully processed {$updatedCount} products.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to process bulk action. Please try again.');
        }
    }

    /**
     * Toggle featured status for a product
     */
    public function toggleFeatured(Product $product)
    {
        try {
            $product->update(['is_featured' => !$product->is_featured]);
            
            $status = $product->is_featured ? 'featured' : 'unfeatured';
            
            return redirect()->back()
                ->with('success', "Product {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('Toggle featured failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to update featured status. Please try again.');
        }
    }

    /**
     * Duplicate a product
     */
    public function duplicate(Product $product)
    {
        try {
            $duplicatedProduct = $this->productService->duplicateProduct($product);
            
            return redirect()->route('staff.products.edit', $duplicatedProduct)
                ->with('success', 'Product duplicated successfully. Please review and update the details.');
        } catch (\Exception $e) {
            Log::error('Product duplication failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to duplicate product. Please try again.');
        }
    }

    /**
     * Get products data for AJAX requests
     * Optimized with selective eager loading
     */
    public function getData(Request $request)
    {
        $query = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'inventory:id,product_id,quantity_available'
        ]);
        
        // Apply filters similar to index method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        $products = $query->paginate(10);
        
        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total()
            ]
        ]);
    }

    /**
     * Quick update product field (AJAX)
     */
    public function quickUpdate(Request $request, Product $product)
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');
            
            // Validate allowed fields
            $allowedFields = ['price', 'status', 'is_featured'];
            
            if (!in_array($field, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid field'
                ], 400);
            }
            
            // Validate value based on field
            if ($field === 'price') {
                if (!is_numeric($value) || $value < 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid price value'
                    ], 400);
                }
            } elseif ($field === 'status') {
                $validStatuses = ['active', 'inactive', 'out_of_stock', 'discontinued'];
                if (!in_array($value, $validStatuses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status value'
                    ], 400);
                }
            }
            
            // Update the field
            $product->update([$field => $value]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Quick update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product'
            ], 500);
        }
    }

    /**
     * Set primary image for product (AJAX)
     */
    public function setPrimaryImage(Request $request, $imageId)
    {
        try {
            $image = \App\Models\ProductImage::findOrFail($imageId);
            $product = $image->product;
            
            // Remove primary flag from all images of this product
            $product->images()->update(['is_primary' => false]);
            
            // Set this image as primary
            $image->update(['is_primary' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Primary image updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Set primary image failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary image'
            ], 500);
        }
    }

    /**
     * Delete a product image (AJAX)
     */
    public function deleteImage($imageId)
    {
        try {
            $image = \App\Models\ProductImage::findOrFail($imageId);
            $imageUploadService = app(\App\Services\ImageUploadService::class);
            
            // Delete the image using the service
            $imageUploadService->deleteProductImage($image);
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete image failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    /**
     * Reorder product images (AJAX)
     */
    public function reorderImages(Request $request, Product $product)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:product_images,id',
            'order.*.order' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();
            
            foreach ($request->order as $item) {
                \App\Models\ProductImage::where('id', $item['id'])
                    ->where('product_id', $product->id)
                    ->update(['display_order' => $item['order']]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Image order updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reorder images failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder images'
            ], 500);
        }
    }

    /**
     * Upload images for a product (AJAX)
     */
    public function uploadImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
        ]);

        try {
            $imageUploadService = app(\App\Services\ImageUploadService::class);
            
            $uploadedImages = $imageUploadService->uploadProductImages(
                $product,
                $request->file('images'),
                [
                    'alt_texts' => $request->input('alt_texts', [])
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'images' => collect($uploadedImages)->map(function($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->full_url,
                        'is_primary' => $image->is_primary,
                        'display_order' => $image->display_order
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Upload images failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show product status and visibility management interface
     * Uses cached stats for better performance
     */
    public function manageVisibility()
    {
        $stats = Cache::remember('products.visibility_stats', 300, function () {
            return [
                'active' => Product::where('status', 'active')->count(),
                'inactive' => Product::where('status', 'inactive')->count(),
                'out_of_stock' => Product::where('status', 'out_of_stock')->count(),
                'discontinued' => Product::where('status', 'discontinued')->count(),
                'featured' => Product::where('is_featured', true)->count(),
                'new_arrivals' => Product::where('is_new_arrival', true)->count(),
                'best_sellers' => Product::where('is_best_seller', true)->count(),
            ];
        });

        $products = Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,image_url,alt_text',
            'inventory:id,product_id,quantity_available'
        ])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return view('staff.products.visibility', compact('stats', 'products'));
    }

    /**
     * Update product visibility settings
     */
    public function updateVisibility(Request $request, Product $product)
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,discontinued,out_of_stock',
            'is_featured' => 'sometimes|boolean',
            'is_new_arrival' => 'sometimes|boolean',
            'is_best_seller' => 'sometimes|boolean',
        ]);

        try {
            $this->productService->updateVisibility($product, $request->all());
            
            // Clear visibility stats cache
            Cache::forget('products.visibility_stats');
            
            return redirect()->back()
                ->with('success', 'Product visibility updated successfully.');
        } catch (\Exception $e) {
            Log::error('Update visibility failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to update visibility. Please try again.');
        }
    }

    /**
     * Bulk update product visibility
     */
    public function bulkUpdateVisibility(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
            'status' => 'sometimes|in:active,inactive,discontinued,out_of_stock',
            'is_featured' => 'sometimes|boolean',
            'is_new_arrival' => 'sometimes|boolean',
            'is_best_seller' => 'sometimes|boolean',
        ]);

        try {
            $visibilityData = $request->only(['status', 'is_featured', 'is_new_arrival', 'is_best_seller']);
            $updatedCount = $this->productService->bulkUpdateVisibility($request->products, $visibilityData);
            
            // Clear visibility stats cache
            Cache::forget('products.visibility_stats');
            
            return redirect()->back()
                ->with('success', "Successfully updated visibility for {$updatedCount} products.");
        } catch (\Exception $e) {
            Log::error('Bulk update visibility failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to update visibility. Please try again.');
        }
    }

    /**
     * Toggle marketing flag (featured, new arrival, best seller)
     */
    public function toggleMarketingFlag(Request $request, Product $product)
    {
        $request->validate([
            'flag' => 'required|in:is_featured,is_new_arrival,is_best_seller'
        ]);

        try {
            $this->productService->toggleMarketingFlag($product, $request->flag);
            
            $flagName = str_replace('is_', '', $request->flag);
            $flagName = str_replace('_', ' ', $flagName);
            
            return redirect()->back()
                ->with('success', ucfirst($flagName) . ' status toggled successfully.');
        } catch (\Exception $e) {
            Log::error('Toggle marketing flag failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to toggle flag. Please try again.');
        }
    }

    /**
     * Show customer preview for a product
     */
    public function customerPreview(Product $product)
    {
        try {
            $previewData = $this->productService->getCustomerPreview($product);
            
            return view('staff.products.preview', $previewData);
        } catch (\Exception $e) {
            Log::error('Customer preview failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to load preview. Please try again.');
        }
    }

    /**
     * Quick toggle visibility status (AJAX)
     */
    public function quickToggleVisibility(Request $request, Product $product)
    {
        $request->validate([
            'field' => 'required|in:status,is_featured,is_new_arrival,is_best_seller',
            'value' => 'required'
        ]);

        try {
            $field = $request->field;
            $value = $request->value;

            if ($field === 'status') {
                $validStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
                if (!in_array($value, $validStatuses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status value'
                    ], 400);
                }
            } else {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $product->update([$field => $value]);
            
            return response()->json([
                'success' => true,
                'message' => 'Visibility updated successfully',
                'product' => $product->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Quick toggle visibility failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update visibility'
            ], 500);
        }
    }
}