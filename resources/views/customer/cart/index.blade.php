<x-customer-layout>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if($cart && $cart->items->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Cart Items -->
                    <div class="lg:col-span-2 space-y-4">
                        @foreach($cart->items as $item)
                            <div class="bg-white rounded-lg shadow-md p-6 flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="w-24 h-24 flex-shrink-0">
                                    @if($item->product->images->count() > 0)
                                        <img src="{{ Storage::url($item->product->images->first()->image_url) }}" 
                                             alt="{{ $item->product->name }}"
                                             class="w-full h-full object-cover rounded">
                                    @else
                                        <div class="w-full h-full bg-gray-200 rounded flex items-center justify-center text-gray-400">
                                            No Image
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Details -->
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <a href="{{ route('products.show', $item->product) }}" class="hover:text-purple-600">
                                            {{ $item->product->name }}
                                        </a>
                                    </h3>
                                    @if($item->product->brand)
                                        <p class="text-sm text-gray-500">{{ $item->product->brand->name }}</p>
                                    @endif
                                    <p class="text-lg font-bold text-purple-600 mt-2">
                                        ₱{{ number_format($item->price_at_time, 2) }}
                                    </p>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-2">
                                    <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center space-x-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" 
                                               name="quantity" 
                                               value="{{ $item->quantity }}" 
                                               min="1" 
                                               max="{{ $item->product->total_stock }}"
                                               class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                            Update
                                        </button>
                                    </form>
                                </div>

                                <!-- Subtotal -->
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">
                                        ₱{{ number_format($item->subtotal, 2) }}
                                    </p>
                                </div>

                                <!-- Remove Button -->
                                <form action="{{ route('cart.remove', $item) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 transition"
                                            onclick="return confirm('Are you sure you want to remove this item?')">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-gray-600">
                                    <span>Items ({{ $cart->total_items }})</span>
                                    <span>₱{{ number_format($cart->total_price, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span>Calculated at checkout</span>
                                </div>
                                <div class="border-t pt-3 flex justify-between text-lg font-bold text-gray-900">
                                    <span>Total</span>
                                    <span>₱{{ number_format($cart->total_price, 2) }}</span>
                                </div>
                            </div>

                            <button class="w-full bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition-all duration-300 transform hover:scale-105 mb-3">
                                Proceed to Checkout
                            </button>

                            <a href="{{ route('products.index') }}" 
                               class="block w-full text-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                                Continue Shopping
                            </a>

                            <form action="{{ route('cart.clear') }}" method="POST" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full text-red-600 hover:text-red-800 text-sm transition"
                                        onclick="return confirm('Are you sure you want to clear your cart?')">
                                    Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty Cart -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
                    <p class="text-gray-600 mb-6">Add some products to get started!</p>
                    <a href="{{ route('products.index') }}" 
                       class="inline-block bg-gradient-to-r from-pink-500 to-purple-500 text-white px-8 py-3 rounded-lg font-semibold hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        Start Shopping
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-customer-layout>
