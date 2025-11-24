<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffPricingController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display pricing management interface.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variants']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->paginate(20);

        return view('staff.pricing.index', compact('products'));
    }

    /**
     * Show pricing edit form for a specific product.
     */
    public function edit(Product $product)
    {
        $product->load(['variants', 'category', 'brand']);

        return view('staff.pricing.edit', compact('product'));
    }

    /**
     * Update pricing for a specific product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:base_price',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->productService->updatePricing($product, $validated);

            return redirect()
                ->route('staff.pricing.edit', $product)
                ->with('success', 'Product pricing updated successfully.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Show bulk pricing update form.
     */
    public function bulkEdit(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        
        if (empty($productIds)) {
            return redirect()
                ->route('staff.pricing.index')
                ->with('error', 'Please select at least one product.');
        }

        $products = Product::with(['category', 'brand'])
            ->whereIn('id', $productIds)
            ->get();

        return view('staff.pricing.bulk-edit', compact('products', 'productIds'));
    }

    /**
     * Process bulk pricing update.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'update_type' => 'required|in:direct,adjustment',
            'base_price' => 'required_if:update_type,direct|nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'adjustment_type' => 'required_if:update_type,adjustment|nullable|in:percentage,fixed',
            'adjustment_value' => 'required_if:update_type,adjustment|nullable|numeric',
            'apply_to' => 'required_if:update_type,adjustment|nullable|in:base_price,sale_price,both',
        ]);

        try {
            DB::beginTransaction();

            if ($validated['update_type'] === 'direct') {
                $pricingData = array_filter([
                    'base_price' => $validated['base_price'] ?? null,
                    'sale_price' => $validated['sale_price'] ?? null,
                    'cost_price' => $validated['cost_price'] ?? null,
                ]);
            } else {
                $pricingData = [
                    'adjustment_type' => $validated['adjustment_type'],
                    'adjustment_value' => $validated['adjustment_value'],
                    'apply_to' => $validated['apply_to'],
                ];
            }

            $count = $this->productService->bulkUpdatePricing(
                $validated['product_ids'],
                $pricingData
            );

            DB::commit();

            return redirect()
                ->route('staff.pricing.index')
                ->with('success', "Successfully updated pricing for {$count} products.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Failed to update pricing: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Update variant pricing.
     */
    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        $validated = $request->validate([
            'price_adjustment' => 'required|numeric',
        ]);

        try {
            $this->productService->updateVariantPricing($variant->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Variant pricing updated successfully.',
                'effective_price' => $variant->fresh()->effective_price,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variant pricing: ' . $e->getMessage(),
            ], 422);
        }
    }
}
