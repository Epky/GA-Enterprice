<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Preview - {{ $product->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Preview Notice Banner -->
    <div class="bg-yellow-500 text-white px-4 py-3 text-center">
        <strong>Preview Mode:</strong> This is how customers will see this product. 
        <a href="{{ route('staff.products.edit', $product) }}" class="underline ml-2">Edit Product</a>
        <button onclick="window.close()" class="underline ml-2">Close Preview</button>
    </div>

    <!-- Visibility Status Banner -->
    @if (!$is_visible)
        <div class="bg-red-500 text-white px-4 py-3 text-center">
            <strong>Not Visible:</strong> This product is currently {{ $product->status }} and will not be shown to customers.
        </div>
    @endif

    @if (!$is_purchasable && $is_visible)
        <div class="bg-orange-500 text-white px-4 py-3 text-center">
            <strong>Not Purchasable:</strong> This product is visible but cannot be purchased ({{ $stock_status }}).
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
                <!-- Product Images -->
                <div>
                    @if ($product->images->count() > 0)
                        <div class="mb-4">
                            <img src="{{ $product->primaryImage->image_url ?? $product->images->first()->image_url }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-full h-96 object-cover rounded-lg">
                        </div>
                        @if ($product->images->count() > 1)
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ($product->images->take(4) as $image)
                                    <img src="{{ $image->image_url }}" 
                                         alt="{{ $product->name }}" 
                                         class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75">
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400 text-xl">No Image Available</span>
                        </div>
                    @endif
                </div>

                <!-- Product Details -->
                <div>
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if ($display_badges['featured'])
                            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                Featured
                            </span>
                        @endif
                        @if ($display_badges['new_arrival'])
                            <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                New Arrival
                            </span>
                        @endif
                        @if ($display_badges['best_seller'])
                            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                Best Seller
                            </span>
                        @endif
                        @if ($display_badges['on_sale'])
                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                On Sale
                            </span>
                        @endif
                    </div>

                    <!-- Product Name -->
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>

                    <!-- Brand and Category -->
                    <div class="text-sm text-gray-600 mb-4">
                        @if ($product->brand)
                            <span>Brand: <strong>{{ $product->brand->name }}</strong></span>
                        @endif
                        @if ($product->category)
                            <span class="ml-4">Category: <strong>{{ $product->category->name }}</strong></span>
                        @endif
                    </div>

                    <!-- Rating (if available) -->
                    @if ($product->average_rating > 0)
                        <div class="flex items-center mb-4">
                            <div class="flex text-yellow-400">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $product->average_rating)
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 fill-current text-gray-300" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    @endif
                                @endfor
                            </div>
                            <span class="ml-2 text-sm text-gray-600">({{ $product->review_count }} reviews)</span>
                        </div>
                    @endif

                    <!-- Price -->
                    <div class="mb-6">
                        @if ($product->is_on_sale)
                            <div class="flex items-baseline">
                                <span class="text-3xl font-bold text-red-600">${{ number_format($effective_price, 2) }}</span>
                                <span class="ml-2 text-xl text-gray-500 line-through">${{ number_format($product->base_price, 2) }}</span>
                                <span class="ml-2 text-sm text-red-600 font-semibold">Save {{ $product->discount_percentage }}%</span>
                            </div>
                        @else
                            <span class="text-3xl font-bold text-gray-900">${{ number_format($effective_price, 2) }}</span>
                        @endif
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        <span class="text-sm font-semibold 
                            {{ $stock_status === 'In Stock' ? 'text-green-600' : ($stock_status === 'Low Stock' ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $stock_status }}
                        </span>
                    </div>

                    <!-- Short Description -->
                    @if ($product->short_description)
                        <div class="mb-6">
                            <p class="text-gray-700">{{ $product->short_description }}</p>
                        </div>
                    @endif

                    <!-- Variants -->
                    @if ($product->variants->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Available Options:</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($product->activeVariants as $variant)
                                    <button class="px-4 py-2 border border-gray-300 rounded hover:border-gray-900 hover:bg-gray-50">
                                        {{ $variant->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Add to Cart Button -->
                    <div class="mb-6">
                        @if ($is_purchasable)
                            <button class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition">
                                Add to Cart
                            </button>
                        @else
                            <button class="w-full bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold cursor-not-allowed" disabled>
                                {{ $stock_status === 'Out of Stock' ? 'Out of Stock' : 'Not Available' }}
                            </button>
                        @endif
                    </div>

                    <!-- SKU -->
                    <div class="text-sm text-gray-600">
                        SKU: {{ $product->sku }}
                    </div>
                </div>
            </div>

            <!-- Full Description and Specifications -->
            <div class="border-t border-gray-200 p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Description -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Description</h2>
                        <div class="text-gray-700 prose">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>

                    <!-- Specifications -->
                    @if ($product->specifications->count() > 0)
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Specifications</h2>
                            <dl class="space-y-2">
                                @foreach ($product->specifications as $spec)
                                    <div class="flex">
                                        <dt class="font-semibold text-gray-900 w-1/3">{{ $spec->spec_name }}:</dt>
                                        <dd class="text-gray-700 w-2/3">{{ $spec->spec_value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Preview Information Panel -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">Preview Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <strong class="text-blue-900">Status:</strong>
                    <span class="ml-2 px-2 py-1 rounded text-xs font-semibold
                        {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
                <div>
                    <strong class="text-blue-900">Visible to Customers:</strong>
                    <span class="ml-2">{{ $is_visible ? 'Yes' : 'No' }}</span>
                </div>
                <div>
                    <strong class="text-blue-900">Purchasable:</strong>
                    <span class="ml-2">{{ $is_purchasable ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
