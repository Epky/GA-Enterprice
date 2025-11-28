<x-customer-layout>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Breadcrumb Card -->
        <div class="bg-white rounded-lg shadow-sm px-6 py-3 mb-6">
            <nav class="flex text-sm text-gray-500">
                <a href="/" class="hover:text-purple-600 transition">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('products.index') }}" class="hover:text-purple-600 transition">Shop</a>
                <span class="mx-2">/</span>
                <a href="{{ route('products.category', $product->category) }}" class="hover:text-purple-600 transition">{{ $product->category->name }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 font-medium">{{ $product->name }}</span>
            </nav>
        </div>

        <!-- Main Product Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                <!-- Product Images -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden border border-gray-200 shadow-inner">
                        @if($product->images->count() > 0)
                            <img id="mainImage" 
                                 src="{{ Storage::url($product->images->first()->image_url) }}" 
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <div class="text-center">
                                    <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-2 font-medium">No Image Available</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Thumbnail Gallery -->
                    @if($product->images->count() > 1)
                        <div class="grid grid-cols-4 gap-3">
                            @foreach($product->images as $image)
                                <button onclick="changeMainImage('{{ Storage::url($image->image_url) }}')" 
                                        class="aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-purple-500 transition-all duration-300 hover:shadow-md">
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
                    <!-- Brand Badge -->
                    @if($product->brand)
                        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 rounded-full border border-purple-200">
                            <svg class="w-4 h-4 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm font-semibold text-purple-700 uppercase tracking-wide">{{ $product->brand->name }}</span>
                        </div>
                    @endif

                    <!-- Product Name -->
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">{{ $product->name }}</h1>

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

                    <!-- Price Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
                        <div class="flex items-baseline space-x-3 flex-wrap">
                            @if($product->is_on_sale)
                                <span class="text-4xl font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">₱{{ number_format($product->sale_price, 2) }}</span>
                                <span class="text-xl text-gray-400 line-through">₱{{ number_format($product->base_price, 2) }}</span>
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-red-500 text-white shadow-md">
                                    🔥 Save {{ $product->discount_percentage }}%
                                </span>
                            @else
                                <span class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">₱{{ number_format($product->base_price, 2) }}</span>
                            @endif
                        </div>
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

                <!-- Product Details -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 space-y-3">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Product Details
                    </h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">SKU:</span>
                            <span class="text-gray-900 font-semibold">{{ $product->sku }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Category:</span>
                            <span class="text-gray-900 font-semibold">{{ $product->category->name ?? 'N/A' }}</span>
                        </div>
                        @if($product->brand)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Brand:</span>
                            <span class="text-gray-900 font-semibold">{{ $product->brand->name }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600 font-medium">Status:</span>
                            <span class="font-semibold {{ $product->in_stock ? 'text-green-600' : 'text-red-600' }}">
                                {{ $product->in_stock ? 'Available' : 'Out of Stock' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                @if($product->description)
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-100">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                            Description
                        </h3>
                        <div class="text-sm text-gray-700 leading-relaxed">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                    <!-- Add to Cart / Login Button -->
                    @auth
                        @if($product->in_stock)
                            <form action="{{ route('cart.add', $product) }}" method="POST" class="space-y-4">
                                @csrf
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="number" 
                                               id="quantity" 
                                               name="quantity" 
                                               value="1" 
                                               min="1" 
                                               max="{{ $product->total_stock }}"
                                               class="w-24 px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-semibold text-center">
                                        <span class="text-sm text-gray-500">Max: {{ $product->total_stock }}</span>
                                    </div>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-pink-500 via-purple-500 to-purple-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 flex items-center justify-center space-x-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>Add to Cart</span>
                                </button>
                            </form>
                        @else
                            <button disabled 
                                    class="w-full bg-gray-200 text-gray-500 px-8 py-4 rounded-xl font-bold text-lg cursor-not-allowed opacity-60">
                                Out of Stock
                            </button>
                        @endif
                    @else
                        <div class="space-y-4">
                            <a href="{{ route('login') }}" 
                               class="block w-full bg-gradient-to-r from-pink-500 via-purple-500 to-purple-600 text-white px-8 py-4 rounded-xl font-bold text-lg text-center shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-1">
                                🔐 Login to Purchase
                            </a>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-sm text-blue-800 text-center flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    You must be logged in to add items to your cart
                                </p>
                            </div>
                        </div>
                    @endauth

                    <!-- Product Features/Badges -->
                    <div class="flex flex-wrap gap-2">
                        @if($product->is_featured)
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-md">
                                ⭐ Featured
                            </span>
                        @endif
                        @if($product->is_new_arrival)
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-md">
                                🆕 New Arrival
                            </span>
                        @endif
                        @if($product->is_best_seller)
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-md">
                                🔥 Best Seller
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Specifications Section -->
        @if($product->specifications->count() > 0)
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Product Specifications
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($product->specifications as $spec)
                        <div class="flex bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                            <dt class="font-semibold text-gray-900 w-1/2">{{ $spec->spec_name }}</dt>
                            <dd class="text-gray-700 w-1/2">{{ $spec->spec_value }}</dd>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Related Products Section -->
        @if($relatedProducts->count() > 0)
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">💎 You May Also Like</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $relatedProduct)
                        <a href="{{ route('products.show', $relatedProduct) }}" 
                           class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group border border-gray-200 hover:border-purple-300">
                            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                                @if($relatedProduct->images->count() > 0)
                                    <img src="{{ Storage::url($relatedProduct->images->first()->image_url) }}" 
                                         alt="{{ $relatedProduct->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 mb-1 line-clamp-2 group-hover:text-purple-600 transition">{{ $relatedProduct->name }}</h3>
                                <p class="text-xs text-gray-500 mb-3">{{ $relatedProduct->brand->name ?? 'No Brand' }}</p>
                                <div class="flex items-baseline space-x-2">
                                    @if($relatedProduct->is_on_sale)
                                        <span class="text-lg font-bold text-red-600">₱{{ number_format($relatedProduct->sale_price, 2) }}</span>
                                        <span class="text-xs text-gray-400 line-through">₱{{ number_format($relatedProduct->base_price, 2) }}</span>
                                    @else
                                        <span class="text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">₱{{ number_format($relatedProduct->base_price, 2) }}</span>
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
    // Image gallery functionality with smooth transition
    function changeMainImage(imageUrl) {
        const mainImage = document.getElementById('mainImage');
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = imageUrl;
            mainImage.style.opacity = '1';
        }, 150);
    }

    // Tab switching functionality with modern styling
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active state from all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('bg-white', 'text-purple-600', 'shadow-sm', 'border-purple-500');
            button.classList.add('text-gray-600', 'border-transparent');
        });
        
        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Add active state to selected tab button
        const activeButton = document.getElementById('tab-' + tabName);
        activeButton.classList.remove('text-gray-600', 'border-transparent');
        activeButton.classList.add('bg-white', 'text-purple-600', 'shadow-sm', 'border-purple-500');
    }
</script>
@endpush
</x-customer-layout>
