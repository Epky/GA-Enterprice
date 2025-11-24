<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffPromotionController extends Controller
{
    protected PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Display a listing of promotions.
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Apply filters
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active()->available();
                    break;
                case 'scheduled':
                    $query->where('is_active', true)->where('start_date', '>', now());
                    break;
                case 'expired':
                    $query->where('end_date', '<', now());
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->promotion_type);
        }

        $promotions = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('staff.promotions.index', compact('promotions'));
    }

    /**
     * Show the form for creating a new promotion.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();
        $products = Product::active()->get();

        return view('staff.promotions.create', compact('categories', 'brands', 'products'));
    }

    /**
     * Store a newly created promotion.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promotion_type' => 'required|in:percentage,fixed_amount,bogo,free_shipping',
            'discount_value' => 'required_unless:promotion_type,free_shipping|nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:all,category,product,brand',
            'applicable_ids' => 'required_unless:applicable_to,all|nullable|array',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $promotion = $this->promotionService->createPromotion($validated);

            return redirect()
                ->route('staff.promotions.show', $promotion)
                ->with('success', 'Promotion created successfully.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified promotion.
     */
    public function show(Promotion $promotion)
    {
        $stats = $this->promotionService->getPromotionStats($promotion);
        $affectedProducts = $promotion->getAffectedProducts()->take(10);

        return view('staff.promotions.show', compact('promotion', 'stats', 'affectedProducts'));
    }

    /**
     * Show the form for editing the specified promotion.
     */
    public function edit(Promotion $promotion)
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();
        $products = Product::active()->get();

        return view('staff.promotions.edit', compact('promotion', 'categories', 'brands', 'products'));
    }

    /**
     * Update the specified promotion.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promotion_type' => 'required|in:percentage,fixed_amount,bogo,free_shipping',
            'discount_value' => 'required_unless:promotion_type,free_shipping|nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'required|in:all,category,product,brand',
            'applicable_ids' => 'required_unless:applicable_to,all|nullable|array',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $this->promotionService->updatePromotion($promotion, $validated);

            return redirect()
                ->route('staff.promotions.show', $promotion)
                ->with('success', 'Promotion updated successfully.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified promotion.
     */
    public function destroy(Promotion $promotion)
    {
        try {
            $this->promotionService->deletePromotion($promotion);

            return redirect()
                ->route('staff.promotions.index')
                ->with('success', 'Promotion deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete promotion: ' . $e->getMessage());
        }
    }

    /**
     * Toggle promotion status.
     */
    public function toggleStatus(Promotion $promotion)
    {
        $this->promotionService->toggleStatus($promotion);

        return response()->json([
            'success' => true,
            'is_active' => $promotion->is_active,
            'message' => 'Promotion status updated successfully.',
        ]);
    }

    /**
     * Duplicate a promotion.
     */
    public function duplicate(Promotion $promotion)
    {
        try {
            $newPromotion = $this->promotionService->duplicatePromotion($promotion);

            return redirect()
                ->route('staff.promotions.edit', $newPromotion)
                ->with('success', 'Promotion duplicated successfully. Please review and activate.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to duplicate promotion: ' . $e->getMessage());
        }
    }

    /**
     * Show products affected by promotion.
     */
    public function affectedProducts(Promotion $promotion)
    {
        $products = $promotion->getAffectedProducts();

        return view('staff.promotions.affected-products', compact('promotion', 'products'));
    }
}
