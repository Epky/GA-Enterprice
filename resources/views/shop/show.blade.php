@extends('layouts.landing')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-gray-500">
                <a href="/" class="hover:text-purple-600">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('products.index') }}" class="hover:text-purple-600">Shop</a>
                <span class="mx-2">/</span>
                <a href="{{ route('products.category', $product->category) }}" class="hover:text-purple-600">{{ $product->category->name }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Product Detail Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
            <!-- Product Images -->
            <div class="space-y-4">
                <!-- Main Image -->
                <div class="aspect-square bg-white rounded-lg shadow-lg overflow-hidden">
                    @if($product->images->count() > 0)
                        <img id="mainImage" 
                             src="{{ Storage::url($product->images->first()->image_url) }}" 
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2">No Image Available</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Thumbnail Gallery -->
                @if($product->images->count() > 1)
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($product->images as $image)
                            <button onclick="changeMainImage('{{ Storage::url($image->image_url) }}')" 
                                    class="aspect-square bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden border-2 border-transparent hover:border-purple-500">
                                <img src="{{ Storage::url($image->image_url) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Information -->
            <div class="space-y-6">
                <!-- Brand -->
                @if($product->brand)
                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        {{ $product->brand->name }}
                    </div>
                @endif

                <!-- Product Name -->
                <h1 class="text-4xl font-bold text-gray-900">{{ $product->name }}</h1>

                <!-- Rating (if available) -->
                @if($product->average_rating > 0)
                    <div class="flex items-center space-x-2">
                        <div class="flex text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->average_rating))
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
                        <span class="text-sm text-gray-600">({{ $product->review_count }} reviews)</span>
                    </div>
                @endif

                <!-- Price -->
                <div class="flex items-baseline space-x-3">
                    @if($product->is_on_sale)
                        <span class="text-4xl font-bold text-red-600">₱{{ number_format($product->sale_price, 2) }}</span>
                        <span class="text-2xl text-gray-400 line-through">₱{{ number_format($product->base_price, 2) }}</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            Save {{ $product->discount_percentage }}%
                        </span>
                    @else
                        <span class="text-4xl font-bold text-purple-600">₱{{ number_format($product->base_price, 2) }}</span>
                    @endif
                </div>

                <!-- Stock Availability -->
                <div class="flex items-center space-x-2">
                    @if($product->in_stock)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            In Stock ({{ $product->total_stock }} available)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Out of Stock
                        </span>
                    @endif
                </div>

                <!-- Description -->
                @if($product->short_description)
                    <div class="prose prose-sm text-gray-600">
                        <p>{{ $product->short_description }}</p>
                    </div>
                @endif

                <!-- Add to Cart / Login Button -->
                @auth
                    @if($product->in_stock)
                        <form action="{{ route('cart.add', $product) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="flex items-center space-x-4">
                                <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                                <input type="number" 
                                       id="quantity" 
                                       name="quantity" 
                                       value="1" 
                                       min="1" 
                                       max="{{ $product->total_stock }}"
                                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-pink-500 to-purple-500 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                Add to Cart
                            </button>
                        </form>
                    @else
                        <button disabled 
                                class="w-full bg-gray-300 text-gray-500 px-8 py-4 rounded-lg font-semibold text-lg cursor-not-allowed">
                            Out of Stock
                        </button>
                    @endif
                @else
                    <div class="space-y-4">
                        <a href="{{ route('login') }}" 
                           class="block w-full bg-gradient-to-r from-pink-500 to-purple-500 text-white px-8 py-4 rounded-lg font-semibold text-lg text-center hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            Login to Purchase
                        </a>
                        <p class="text-sm text-gray-600 text-center">
                            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            You must be logged in to add items to your cart
                        </p>
                    </div>
                @endauth

                <!-- Product Features/Badges -->
                <div class="flex flex-wrap gap-2">
                    @if($product->is_featured)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            ⭐ Featured
                        </span>
                    @endif
                    @if($product->is_new_arrival)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            🆕 New Arrival
                        </span>
                    @endif
                    @if($product->is_best_seller)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            🔥 Best Seller
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Details Tabs -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-16">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('description')" 
                            id="tab-description"
                            class="tab-button border-b-2 border-purple-500 py-4 px-1 text-sm font-medium text-purple-600">
                        Description
                    </button>
                    @if($product->specifications->count() > 0)
                        <button onclick="showTab('specifications')" 
                                id="tab-specifications"
                                class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Specifications
                        </button>
                    @endif
                </nav>
            </div>

            <!-- Description Tab -->
            <div id="content-description" class="tab-content">
                <div class="prose max-w-none text-gray-700">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>

            <!-- Specifications Tab -->
            @if($product->specifications->count() > 0)
                <div id="content-specifications" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($product->specifications as $spec)
                            <div class="flex border-b border-gray-200 py-3">
                                <dt class="font-medium text-gray-900 w-1/2">{{ $spec->spec_name }}</dt>
                                <dd class="text-gray-700 w-1/2">{{ $spec->spec_value }}</dd>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Related Products Section -->
        @if($relatedProducts->count() > 0)
            <div class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">You May Also Like</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $relatedProduct)
                        <a href="{{ route('products.show', $relatedProduct) }}" 
                           class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            <div class="aspect-square bg-gray-200 overflow-hidden">
                                @if($relatedProduct->images->count() > 0)
                                    <img src="{{ Storage::url($relatedProduct->images->first()->image_url) }}" 
                                         alt="{{ $relatedProduct->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        No Image
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $relatedProduct->name }}</h3>
                                <p class="text-sm text-gray-500 mb-2">{{ $relatedProduct->brand->name ?? 'No Brand' }}</p>
                                <div class="flex items-baseline space-x-2">
                                    @if($relatedProduct->is_on_sale)
                                        <span class="text-lg font-bold text-red-600">₱{{ number_format($relatedProduct->sale_price, 2) }}</span>
                                        <span class="text-sm text-gray-400 line-through">₱{{ number_format($relatedProduct->base_price, 2) }}</span>
                                    @else
                                        <span class="text-lg font-bold text-purple-600">₱{{ number_format($relatedProduct->base_price, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Image gallery functionality
    function changeMainImage(imageUrl) {
        document.getElementById('mainImage').src = imageUrl;
    }

    // Tab switching functionality
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active state from all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-purple-500', 'text-purple-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Add active state to selected tab button
        const activeButton = document.getElementById('tab-' + tabName);
        activeButton.classList.remove('border-transparent', 'text-gray-500');
        activeButton.classList.add('border-purple-500', 'text-purple-600');
    }
</script>
@endpush
@endsection
