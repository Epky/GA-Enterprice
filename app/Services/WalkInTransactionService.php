<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class WalkInTransactionService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * Create a new walk-in transaction.
     */
    public function createTransaction(array $data): Order
    {
        // Validate customer name is not whitespace only
        if (empty(trim($data['customer_name']))) {
            throw ValidationException::withMessages([
                'customer_name' => 'Customer name cannot be empty or whitespace only.'
            ]);
        }

        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => Auth::id(),
                'order_type' => 'walk_in',
                'order_status' => 'pending',
                'payment_status' => 'pending',
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);

            return $order;
        });
    }

    /**
     * Generate unique order number for walk-in transactions.
     */
    private function generateOrderNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'WI-' . $date . '-';
        
        // Get the last order number for today
        $lastOrder = Order::where('order_number', 'LIKE', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Search products by name or SKU.
     */
    public function searchProducts(string $query): \Illuminate\Database\Eloquent\Collection
    {
        \Illuminate\Support\Facades\Log::info('Searching products in service', [
            'query' => $query,
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
        ]);
        
        // Use LIKE for case-insensitive search (works on both MySQL and PostgreSQL)
        $products = Product::with(['inventory', 'primaryImage'])
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get();
            
        \Illuminate\Support\Facades\Log::info('Search results', [
            'query' => $query,
            'found' => $products->count(),
        ]);
        
        return $products;
    }

    /**
     * Add item to transaction.
     */
    public function addItem(Order $order, array $itemData): OrderItem
    {
        if (!$order->isPending()) {
            throw ValidationException::withMessages([
                'order' => 'Cannot add items to a non-pending order.'
            ]);
        }

        $product = Product::with('inventory')->findOrFail($itemData['product_id']);
        $quantity = $itemData['quantity'] ?? 1;

        // Validate quantity is positive integer
        if (!is_int($quantity) || $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be a positive integer.'
            ]);
        }

        return DB::transaction(function () use ($order, $product, $quantity, $itemData) {
            // Check if product already exists in cart
            $existingItem = $order->orderItems()
                ->where('product_id', $product->id)
                ->where(function ($query) use ($itemData) {
                    if (isset($itemData['variant_id'])) {
                        $query->where('variant_id', $itemData['variant_id']);
                    } else {
                        $query->whereNull('variant_id');
                    }
                })
                ->first();

            if ($existingItem) {
                // Update existing item quantity
                $newQuantity = $existingItem->quantity + $quantity;
                return $this->updateItemQuantity($existingItem, $newQuantity);
            }

            // Check stock availability for new item
            $availableStock = $product->available_stock;
            if ($availableStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock. Available: {$availableStock}"
                ]);
            }

            $unitPrice = $product->effective_price;
            $totalPrice = $unitPrice * $quantity;

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $itemData['variant_id'] ?? null,
                'product_name' => $product->name,
                'variant_name' => null,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_price' => $totalPrice,
            ]);

            // Find the inventory location with available stock
            $location = $this->findInventoryLocation($product, $itemData['variant_id'] ?? null);

            // Reserve stock for this order item
            $this->inventoryService->reserveStock($product, $quantity, [
                'variant_id' => $itemData['variant_id'] ?? null,
                'location' => $location,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'notes' => "Reserved for walk-in transaction: {$order->order_number}",
            ]);

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            return $orderItem;
        });
    }

    /**
     * Update item quantity.
     */
    public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
    {
        if (!$item->order->isPending()) {
            throw ValidationException::withMessages([
                'order' => 'Cannot update items in a non-pending order.'
            ]);
        }

        // Validate quantity is positive integer
        if (!is_int($quantity) || $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be a positive integer.'
            ]);
        }

        $product = $item->product;
        $oldQuantity = $item->quantity;
        $quantityDifference = $quantity - $oldQuantity;

        // Check stock availability (including currently reserved quantity)
        $availableStock = $product->available_stock + $oldQuantity; // Add back the currently reserved quantity
        if ($availableStock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock. Available: " . ($availableStock - $oldQuantity)
            ]);
        }

        return DB::transaction(function () use ($item, $quantity, $quantityDifference, $product, $oldQuantity) {
            // Find the inventory location
            $location = $this->findInventoryLocation($product, $item->variant_id);

            // Adjust reservation based on quantity change
            if ($quantityDifference > 0) {
                // Increasing quantity - reserve more stock
                $this->inventoryService->reserveStock($product, $quantityDifference, [
                    'variant_id' => $item->variant_id,
                    'location' => $location,
                    'reference_type' => 'order',
                    'reference_id' => $item->order_id,
                    'notes' => "Additional reservation for walk-in transaction: {$item->order->order_number}",
                ]);
            } elseif ($quantityDifference < 0) {
                // Decreasing quantity - release some stock
                $this->inventoryService->releaseReservedStock($product, abs($quantityDifference), [
                    'variant_id' => $item->variant_id,
                    'location' => $location,
                    'reference_type' => 'order',
                    'reference_id' => $item->order_id,
                    'notes' => "Released reservation for walk-in transaction: {$item->order->order_number}",
                ]);
            }

            // Update order item
            $item->quantity = $quantity;
            $item->total_price = $item->unit_price * $quantity;
            $item->save();

            // Recalculate order totals
            $this->recalculateOrderTotals($item->order);

            return $item->fresh();
        });
    }

    /**
     * Remove item from transaction.
     */
    public function removeItem(OrderItem $item): bool
    {
        if (!$item->order->isPending()) {
            throw ValidationException::withMessages([
                'order' => 'Cannot remove items from a non-pending order.'
            ]);
        }

        return DB::transaction(function () use ($item) {
            $order = $item->order;
            $product = $item->product;
            $quantity = $item->quantity;

            // Find the inventory location
            $location = $this->findInventoryLocation($product, $item->variant_id);

            // Release reserved stock
            $this->inventoryService->releaseReservedStock($product, $quantity, [
                'variant_id' => $item->variant_id,
                'location' => $location,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'notes' => "Released reservation - item removed from walk-in transaction: {$order->order_number}",
            ]);

            $item->delete();

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            return true;
        });
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotal(Order $order): array
    {
        $items = $order->orderItems;
        
        $subtotal = $items->sum('total_price');
        $taxAmount = 0;
        $discountAmount = 0;
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
        ];
    }

    /**
     * Recalculate and update order totals.
     */
    private function recalculateOrderTotals(Order $order): void
    {
        $totals = $this->calculateTotal($order);
        
        $order->update([
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'discount_amount' => $totals['discount_amount'],
            'total_amount' => $totals['total_amount'],
        ]);
    }

    /**
     * Complete the transaction.
     */
    public function completeTransaction(Order $order, array $paymentData): Order
    {
        if (!$order->isPending()) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be completed.'
            ]);
        }

        if ($order->orderItems->isEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'Cannot complete an order with no items.'
            ]);
        }

        return DB::transaction(function () use ($order, $paymentData) {
            // Refresh order to get latest order items
            $order->load('orderItems');
            
            // Convert reserved stock to sold for each item
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                
                // Find the inventory location
                $location = $this->findInventoryLocation($product, $item->variant_id);
                
                $inventory = $product->inventory()
                    ->where('location', $location)
                    ->where(function ($query) use ($item) {
                        if ($item->variant_id) {
                            $query->where('variant_id', $item->variant_id);
                        } else {
                            $query->whereNull('variant_id');
                        }
                    })
                    ->first();

                if ($inventory) {
                    // Refresh inventory to get latest quantities
                    $inventory->refresh();
                    // Fulfill the reserved quantity (converts reserved to sold)
                    $inventory->fulfillReservedQuantity($item->quantity);
                }

                // Create inventory movement record for the sale
                $this->inventoryService->trackStockMovement(
                    $product,
                    -$item->quantity,
                    'sale',
                    [
                        'variant_id' => $item->variant_id,
                        'location' => $location,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'notes' => "Walk-in transaction completed: {$order->order_number}",
                    ]
                );
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentData['payment_method'],
                'payment_status' => 'completed',
                'amount' => $order->total_amount,
                'paid_at' => now(),
            ]);

            // Update order status
            $order->update([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'confirmed_at' => now(),
            ]);

            return $order->fresh();
        });
    }

    /**
     * Cancel a transaction.
     */
    public function cancelTransaction(Order $order): bool
    {
        if (!$order->isPending()) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be cancelled.'
            ]);
        }

        return DB::transaction(function () use ($order) {
            // Release all reserved stock for this order
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                
                // Find the inventory location
                $location = $this->findInventoryLocation($product, $item->variant_id);
                
                $this->inventoryService->releaseReservedStock($product, $item->quantity, [
                    'variant_id' => $item->variant_id,
                    'location' => $location,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'notes' => "Released reservation - transaction cancelled: {$order->order_number}",
                ]);
            }

            // Update order status
            $order->update([
                'order_status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Generate receipt data.
     */
    public function generateReceipt(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'transaction_date' => $order->confirmed_at ?? $order->created_at,
            'staff_name' => $order->staff->name ?? 'N/A',
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->total_price,
                ];
            }),
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
        ];
    }

    /**
     * Get transaction history with filters.
     */
    public function getTransactionHistory(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['orderItems', 'staff', 'payment'])
            ->walkIn();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_name', 'ILIKE', "%{$search}%");
            });
        }

        // Apply date range filter
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('order_status', $filters['status']);
        }

        // Order by date descending
        $query->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Find the inventory location with available stock for a product.
     */
    private function findInventoryLocation(Product $product, ?int $variantId = null): string
    {
        // First, try to find inventory with available stock
        $inventory = $product->inventory()
            ->where(function ($query) use ($variantId) {
                if ($variantId) {
                    $query->where('variant_id', $variantId);
                } else {
                    $query->whereNull('variant_id');
                }
            })
            ->where('quantity_available', '>', 0)
            ->orderBy('quantity_available', 'desc')
            ->first();

        if ($inventory) {
            return $inventory->location;
        }

        // If no available stock, check for any inventory with reserved stock
        // (for operations like releasing or fulfilling reservations)
        $inventory = $product->inventory()
            ->where(function ($query) use ($variantId) {
                if ($variantId) {
                    $query->where('variant_id', $variantId);
                } else {
                    $query->whereNull('variant_id');
                }
            })
            ->where('quantity_reserved', '>', 0)
            ->orderBy('quantity_reserved', 'desc')
            ->first();

        if ($inventory) {
            return $inventory->location;
        }

        // If still no inventory found, check for any inventory record
        $inventory = $product->inventory()
            ->where(function ($query) use ($variantId) {
                if ($variantId) {
                    $query->where('variant_id', $variantId);
                } else {
                    $query->whereNull('variant_id');
                }
            })
            ->first();

        // Return the location if found, otherwise default to main_warehouse
        return $inventory ? $inventory->location : 'main_warehouse';
    }

    /**
     * Calculate available stock for a product considering current cart.
     *
     * @param Product $product
     * @param Order $order
     * @return int Available stock quantity
     */
    public function calculateAvailableStock(Product $product, Order $order): int
    {
        // Get total available stock from inventory
        $totalAvailable = $product->available_stock;
        
        // Subtract quantity already in cart
        $cartQuantity = $this->getCartQuantity($product, $order);
        
        return max(0, $totalAvailable - $cartQuantity);
    }

    /**
     * Get cart quantity for a specific product.
     *
     * @param Product $product
     * @param Order $order
     * @return int Quantity in cart
     */
    public function getCartQuantity(Product $product, Order $order): int
    {
        return $order->orderItems()
            ->where('product_id', $product->id)
            ->sum('quantity');
    }
}
