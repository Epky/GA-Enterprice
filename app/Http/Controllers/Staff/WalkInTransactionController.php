<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\WalkInTransactionService;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalkInTransactionController extends Controller
{
    public function __construct(
        private WalkInTransactionService $transactionService
    ) {}

    /**
     * Display walk-in transaction interface.
     */
    public function index()
    {
        return view('staff.walk-in-transaction.index');
    }

    /**
     * Create a new transaction.
     */
    public function create()
    {
        return view('staff.walk-in-transaction.create');
    }

    /**
     * Store a new transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
        ]);

        try {
            $order = $this->transactionService->createTransaction($validated);

            return redirect()
                ->route('staff.walk-in-transaction.show', $order)
                ->with('success', 'Transaction created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show transaction details.
     */
    public function show(Order $order)
    {
        $order->load(['orderItems.product.primaryImage', 'staff', 'payment']);

        return view('staff.walk-in-transaction.show', compact('order'));
    }

    /**
     * Search products for transaction.
     */
    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');
        
        \Illuminate\Support\Facades\Log::info('Product search request', [
            'query' => $query,
            'query_length' => strlen($query),
        ]);
        
        if (empty($query)) {
            return response()->json([]);
        }

        $products = $this->transactionService->searchProducts($query);
        
        \Illuminate\Support\Facades\Log::info('Product search results', [
            'query' => $query,
            'count' => $products->count(),
            'products' => $products->pluck('name', 'id')->toArray(),
        ]);

        return response()->json($products->map(function ($product) {
            $imageUrl = null;
            
            // Get the primary image
            if ($product->primaryImage && $product->primaryImage->image_url) {
                $imagePath = $product->primaryImage->image_url;
                
                // Check if it's already a full URL
                if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                    $imageUrl = $imagePath;
                } else {
                    // Use local storage path
                    $imageUrl = asset('storage/' . $imagePath);
                }
            }
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->effective_price,
                'stock' => $product->total_stock,
                'image' => $imageUrl,
            ];
        }));
    }

    /**
     * Add item to transaction.
     */
    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->transactionService->addItem($order, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product added to transaction.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update item quantity.
     */
    public function updateItem(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->transactionService->updateItemQuantity($item, $validated['quantity']);

            return response()->json([
                'success' => true,
                'message' => 'Quantity updated.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove item from transaction.
     */
    public function removeItem(OrderItem $item)
    {
        try {
            $this->transactionService->removeItem($item);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from transaction.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete the transaction.
     */
    public function complete(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,credit_card,debit_card,gcash,paymaya',
        ]);

        try {
            $this->transactionService->completeTransaction($order, $validated);

            return redirect()
                ->route('staff.walk-in-transaction.receipt', $order)
                ->with('success', 'Transaction completed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel the transaction.
     */
    public function cancel(Order $order)
    {
        try {
            $this->transactionService->cancelTransaction($order);

            return redirect()
                ->route('staff.walk-in-transaction.index')
                ->with('success', 'Transaction cancelled.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show receipt.
     */
    public function receipt(Order $order)
    {
        $receipt = $this->transactionService->generateReceipt($order);

        return view('staff.walk-in-transaction.receipt', compact('order', 'receipt'));
    }

    /**
     * Transaction history.
     */
    public function history(Request $request)
    {
        $filters = $request->only(['search', 'start_date', 'end_date', 'status']);
        $transactions = $this->transactionService->getTransactionHistory($filters);

        return view('staff.walk-in-transaction.history', compact('transactions', 'filters'));
    }
}
