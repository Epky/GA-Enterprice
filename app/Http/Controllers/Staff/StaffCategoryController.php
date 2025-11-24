<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffCategoryController extends Controller
{
    /**
     * Display a listing of categories with hierarchical structure
     * Requirement 4.1: Validate category name is unique and create hierarchical relationship
     * Optimized with selective eager loading
     */
    public function index(Request $request)
    {
        $query = Category::with([
            'parent:id,name,slug',
            'children:id,parent_id,name,slug,is_active',
            'products:id,category_id,name,status'
        ]);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter by parent category
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->root();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'display_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'product_count':
                $query->withCount('products')->orderBy('products_count', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->ordered();
        }
        
        $categories = $query->paginate(20);
        
        // Get root categories for filter dropdown with caching
        $rootCategories = Cache::remember('categories.root', 3600, function () {
            return Category::root()
                ->active()
                ->select('id', 'name', 'slug')
                ->ordered()
                ->get();
        });
        
        return view('staff.categories.index', compact('categories', 'rootCategories'));
    }

    /**
     * Show the form for creating a new category
     * Uses cached categories for parent selection
     */
    public function create()
    {
        // Get all active categories for parent selection with caching
        $parentCategories = Cache::remember('categories.active', 3600, function () {
            return Category::active()
                ->select('id', 'name', 'slug', 'parent_id')
                ->ordered()
                ->get();
        });
        
        return view('staff.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category
     * Requirement 4.1: Validate category name is unique and create hierarchical relationship
     */
    public function store(CategoryRequest $request)
    {

        try {
            DB::beginTransaction();
            
            // Generate unique slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            $category = Category::create($validated);
            
            // Clear category caches
            $this->clearCategoryCaches();
            
            DB::commit();
            
            return redirect()->route('staff.categories.index')
                ->with('success', 'Category created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category creation failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create category. Please try again.');
        }
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        $category->load([
            'parent', 
            'children.products', 
            'products.brand',
            'products.inventory'
        ]);
        
        // Get category statistics
        $stats = [
            'total_products' => $category->total_product_count,
            'direct_products' => $category->products()->count(),
            'active_products' => $category->activeProducts()->count(),
            'child_categories' => $category->children()->count(),
            'depth_level' => $category->depth
        ];
        
        return view('staff.categories.show', compact('category', 'stats'));
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(Category $category)
    {
        // Get all categories except this one and its descendants for parent selection
        $excludeIds = [$category->id];
        $excludeIds = array_merge($excludeIds, $category->getAllDescendants()->pluck('id')->toArray());
        
        $parentCategories = Category::active()
            ->whereNotIn('id', $excludeIds)
            ->ordered()
            ->get();
        
        return view('staff.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category
     * Requirement 4.3: Handle existing products by requiring reassignment or confirmation
     */
    public function update(CategoryRequest $request, Category $category)
    {

        try {
            DB::beginTransaction();
            
            // Handle deactivation of category with products
            if (!$request->boolean('is_active') && $category->is_active && $category->total_product_count > 0) {
                if (!$request->boolean('confirm_deactivation')) {
                    return redirect()->back()
                        ->withInput()
                        ->with('warning', 'This category has ' . $category->total_product_count . ' products. Please confirm deactivation.')
                        ->with('require_confirmation', true);
                }
            }
            
            // Update slug if name changed
            $slug = $category->slug;
            if ($request->name !== $category->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            $category->update($validated);
            
            // Clear category caches
            $this->clearCategoryCaches();
            
            DB::commit();
            
            return redirect()->route('staff.categories.show', $category)
                ->with('success', 'Category updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category update failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update category. Please try again.');
        }
    }

    /**
     * Remove the specified category
     * Requirement 4.3: Handle existing products by requiring reassignment or confirmation
     */
    public function destroy(Category $category)
    {
        try {
            DB::beginTransaction();
            
            // Check if category has products
            if ($category->total_product_count > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete category with products. Please move or delete products first.');
            }
            
            // Check if category has child categories
            if ($category->children()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete category with subcategories. Please move or delete subcategories first.');
            }
            
            $category->delete();
            
            // Clear category caches
            $this->clearCategoryCaches();
            
            DB::commit();
            
            return redirect()->route('staff.categories.index')
                ->with('success', 'Category deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category deletion failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to delete category. Please try again.');
        }
    }

    /**
     * Reorder categories within the same parent
     * Requirement 4.4: Maintain hierarchical structure and display appropriately
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        try {
            DB::beginTransaction();
            
            foreach ($request->categories as $categoryData) {
                Category::where('id', $categoryData['id'])
                    ->where('parent_id', $request->parent_id)
                    ->update(['display_order' => $categoryData['display_order']]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category reordering failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories.'
            ], 500);
        }
    }

    /**
     * Move category to different parent
     */
    public function move(Request $request, Category $category)
    {
        $request->validate([
            'new_parent_id' => 'nullable|exists:categories,id',
            'display_order' => 'nullable|integer|min:0'
        ]);

        try {
            DB::beginTransaction();
            
            // Validate move operation
            if ($request->new_parent_id) {
                if ($request->new_parent_id == $category->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Category cannot be its own parent.'
                    ], 422);
                }
                
                // Check if the new parent is a descendant
                $descendants = $category->getAllDescendants();
                if ($descendants->contains('id', $request->new_parent_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot move category under its own descendant.'
                    ], 422);
                }
            }
            
            // Set display order
            $displayOrder = $request->display_order;
            if ($displayOrder === null) {
                $maxOrder = Category::where('parent_id', $request->new_parent_id)->max('display_order') ?? 0;
                $displayOrder = $maxOrder + 1;
            }
            
            $category->update([
                'parent_id' => $request->new_parent_id,
                'display_order' => $displayOrder
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Category moved successfully.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category move failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to move category.'
            ], 500);
        }
    }

    /**
     * Toggle category active status
     */
    public function toggleStatus(Category $category)
    {
        try {
            // Check if deactivating category with products
            if ($category->is_active && $category->total_product_count > 0) {
                return redirect()->back()
                    ->with('warning', 'Category has ' . $category->total_product_count . ' products. Deactivating will hide them from customers.');
            }
            
            $category->update(['is_active' => !$category->is_active]);
            
            // Clear category caches
            $this->clearCategoryCaches();
            
            $status = $category->is_active ? 'activated' : 'deactivated';
            
            return redirect()->back()
                ->with('success', "Category {$status} successfully.");
                
        } catch (\Exception $e) {
            Log::error('Category status toggle failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to update category status. Please try again.');
        }
    }

    /**
     * Get category tree data for AJAX requests
     */
    public function getTree(Request $request)
    {
        $parentId = $request->get('parent_id');
        
        $query = Category::with(['children' => function($q) {
            $q->ordered();
        }]);
        
        if ($parentId === null || $parentId === 'root') {
            $query->root();
        } else {
            $query->where('parent_id', $parentId);
        }
        
        $categories = $query->ordered()->get();
        
        return response()->json([
            'categories' => $categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'product_count' => $category->total_product_count,
                    'has_children' => $category->has_children,
                    'display_order' => $category->display_order,
                    'depth' => $category->depth
                ];
            })
        ]);
    }

    /**
     * Bulk operations on categories
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id'
        ]);

        try {
            DB::beginTransaction();
            
            $categories = Category::whereIn('id', $request->category_ids)->get();
            $successCount = 0;
            $errors = [];
            
            foreach ($categories as $category) {
                try {
                    switch ($request->action) {
                        case 'activate':
                            $category->update(['is_active' => true]);
                            $successCount++;
                            break;
                            
                        case 'deactivate':
                            if ($category->total_product_count > 0) {
                                $errors[] = "Category '{$category->name}' has products and cannot be deactivated.";
                            } else {
                                $category->update(['is_active' => false]);
                                $successCount++;
                            }
                            break;
                            
                        case 'delete':
                            if ($category->total_product_count > 0 || $category->children()->exists()) {
                                $errors[] = "Category '{$category->name}' has products or subcategories and cannot be deleted.";
                            } else {
                                $category->delete();
                                $successCount++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to {$request->action} category '{$category->name}': " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "Successfully processed {$successCount} categories.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk category action failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Bulk operation failed. Please try again.');
        }
    }

    /**
     * Store a newly created category via AJAX (inline creation)
     * Requirements: 1.2, 1.3, 3.1, 3.2, 3.5
     */
    public function storeInline(CategoryRequest $request)
    {
        // Enhanced logging
        Log::info('Inline category creation started', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'unknown',
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'has_csrf' => $request->hasHeader('X-CSRF-TOKEN'),
            'data' => $request->except(['_token'])
        ]);

        // Verify this is an AJAX request
        if (!$request->ajax() && !$request->wantsJson()) {
            Log::warning('Non-AJAX request to inline endpoint');
            return response()->json([
                'success' => false,
                'message' => 'This endpoint only accepts AJAX requests.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            // Generate unique slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Get validated data
            $validated = $request->validated();
            $validated['slug'] = $slug;
            
            // Ensure is_active is set
            if (!isset($validated['is_active'])) {
                $validated['is_active'] = true;
            }
            
            // Create category
            $category = Category::create($validated);
            
            // Clear category caches
            $this->clearCategoryCaches();
            
            DB::commit();
            
            Log::info('Inline category created successfully', [
                'category_id' => $category->id,
                'name' => $category->name
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'is_active' => $category->is_active
                ],
                'message' => 'Category created successfully.'
            ], 201);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Inline category validation failed', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed.'
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Inline category creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Get active categories for dropdown refresh
     * Requirements: 3.1, 3.2, 3.5
     */
    public function getActive()
    {
        try {
            // Get cached active categories
            $categories = Cache::remember('categories.active', 3600, function () {
                return Category::active()
                    ->select('id', 'name', 'slug', 'parent_id')
                    ->ordered()
                    ->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $categories->map(function($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'parent_id' => $category->parent_id
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch active categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories.'
            ], 500);
        }
    }

    /**
     * Clear all category-related caches
     */
    private function clearCategoryCaches(): void
    {
        Cache::forget('categories.active');
        Cache::forget('categories.root');
        Cache::tags(['categories'])->flush();
    }
}
