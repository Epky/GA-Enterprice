<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Brand;
use App\Services\ProductService;
use App\Services\ImageUploadService;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
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
        
        return view('admin.products.index', compact('products', 'categories', 'brands'));
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
        
        return view('admin.products.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created product
     */
    public function store(ProductStoreRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->validated(), $request);
            
            return redirect()->route('admin.products.show', $product)
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
        
        return view('admin.products.show', compact('product'));
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
        
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update the specified product
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        try {
            $updatedProduct = $this->productService->updateProduct($product, $request->validated(), $request);
            
            return redirect()->route('admin.products.show', $updatedProduct)
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
     * Remove the specified product (soft delete)
     * Calculates total stock across all locations before deletion
     */
    public function destroy(Product $product)
    {
        try {
            // Calculate total stock across all locations
            $totalStock = $product->total_stock;
            
            // Log deletion attempt with stock information
            Log::info('Admin product deletion initiated', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'total_stock' => $totalStock
            ]);
            
            // Perform soft delete via ProductService
            $this->productService->deleteProduct($product);
            
            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage(), [
                'product_id' => $product->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }

    /**
     * Upload images for a product (AJAX)
     * Delegates to ImageUploadService for processing
     */
    public function uploadImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
        ]);

        try {
            $imageUploadService = app(ImageUploadService::class);
            
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
     * Delete a product image (AJAX)
     * Delegates to ImageUploadService for deletion
     */
    public function deleteImage($imageId)
    {
        try {
            $image = ProductImage::findOrFail($imageId);
            $imageUploadService = app(ImageUploadService::class);
            
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
     * Set primary image for product (AJAX)
     * Delegates to ImageUploadService for primary image management
     */
    public function setPrimaryImage(Request $request, $imageId)
    {
        try {
            $image = ProductImage::findOrFail($imageId);
            $product = $image->product;
            $imageUploadService = app(ImageUploadService::class);
            
            // Set primary image using the service
            $imageUploadService->setPrimaryImage($product, $image);
            
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
     * Toggle the featured status of a product
     * Requirement 3.3: Quick action for toggling featured status
     */
    public function toggleFeatured(Product $product)
    {
        try {
            // Toggle the is_featured status
            $product->is_featured = !$product->is_featured;
            $product->save();
            
            $status = $product->is_featured ? 'featured' : 'unfeatured';
            
            Log::info('Product featured status toggled', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'is_featured' => $product->is_featured
            ]);
            
            return redirect()->back()
                ->with('success', "Product has been {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('Toggle featured failed: ' . $e->getMessage(), [
                'product_id' => $product->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update product featured status. Please try again.');
        }
    }
}
