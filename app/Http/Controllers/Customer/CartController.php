<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Add a product to the cart.
     */
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->total_stock,
        ]);

        $user = Auth::user();
        
        // Get or create cart for the user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check if product already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Update quantity if item already exists
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            // Check if new quantity exceeds stock
            if ($newQuantity > $product->total_stock) {
                return back()->with('error', 'Cannot add more items. Only ' . $product->total_stock . ' available in stock.');
            }
            
            $cartItem->update([
                'quantity' => $newQuantity,
            ]);
        } else {
            // Create new cart item
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price_at_time' => $product->is_on_sale ? $product->sale_price : $product->base_price,
            ]);
        }

        return back()->with('success', 'Product added to cart successfully!');
    }

    /**
     * Display the cart.
     */
    public function index()
    {
        $user = Auth::user();
        $cart = Cart::with(['items.product.images', 'items.product.brand'])
            ->where('user_id', $user->id)
            ->first();

        return view('customer.cart.index', compact('cart'));
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Verify the cart item belongs to the authenticated user
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        // Check stock availability
        if ($request->quantity > $cartItem->product->total_stock) {
            return back()->with('error', 'Only ' . $cartItem->product->total_stock . ' available in stock.');
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Cart updated successfully!');
    }

    /**
     * Remove item from cart.
     */
    public function remove(CartItem $cartItem)
    {
        // Verify the cart item belongs to the authenticated user
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart!');
    }

    /**
     * Clear all items from cart.
     */
    public function clear()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return back()->with('success', 'Cart cleared successfully!');
    }
}
