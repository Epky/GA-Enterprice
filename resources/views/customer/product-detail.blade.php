<x-customer-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('customer.dashboard') }}" class="text-gray-700 hover:text-purple-600">
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500">{{ $product->category->name ?? 'Products' }}</span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 font-medium">{{ Str::limit($product->name, 30) }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Product Detail -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                
                <!-- Product Images -->
                <div>
                    @if($product->images->count() > 0)
                        <div class="mb-4">
                            <img id="mainImage" src="{{ asset('storage/' . $product->primaryImage->image_url) }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-full rounded-lg">
                        </div>
                        @if($product->images->count() > 1)
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($product->images as $image)
                                    <img src="{{ asset('storage/' . $image->image_url) }}" 
                                         alt="{{ $product->name }}" 
                                         class="w-full h-24 object-cover rounded-lg cursor-pointer hover:opacity-75 transition-opacity {{ $image->is_primary ? 'ring-2 ring-purple-500' : '' }}"
                                         onclick="document.getElementById('mainImage').src = this.src">
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="w-full h-96 bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="h-32 w-32 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Product Info -->
                <div>
                    <div class="mb-4">
                        <span class="text-sm text-purple-600 font-medium">{{ $product->category->name ?? 'Uncategorized' }}</span>
                        @if($product->brand)
                            <span class="text-sm text-gray-500"> • {{ $product->brand->name }}</span>
                        @endif
                    </div>
                    
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                    
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-purple-600">₱{{ number_format($product->base_price, 2) }}</span>
                    </div>

                    <div class="mb-6">
                        <p class="text-gray-600 leading-relaxed">{{ $product->description }}</p>
                    </div>

                    <!-- Stock Status -->
                    @php
                        $totalStock = $product->inventory->sum('quantity_available');
                    @endphp
                    <div class="mb-6">
                        @if($totalStock > 0)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                In Stock ({{ $totalStock }} available)
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                Out of Stock
                            </span>
                        @endif
                    </div>

                    <!-- Product Details -->
                    <div class="border-t border-gray-200 pt-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Product Details</h3>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500">SKU</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $product->sku }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ ucfirst($product->status) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Specifications -->
                    @if($product->specifications->count() > 0)
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Specifications</h3>
                            <dl class="space-y-2">
                                @foreach($product->specifications as $spec)
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">{{ $spec->spec_name }}</dt>
                                        <dd class="text-sm font-medium text-gray-900">{{ $spec->spec_value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold" {{ $totalStock <= 0 ? 'disabled' : '' }}>
                            Add to Cart
                        </button>
                        <button class="px-6 py-3 border-2 border-purple-600 text-purple-600 rounded-lg hover:bg-purple-50 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">You May Also Like</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $relatedProduct)
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300 group">
                            <div class="relative overflow-hidden bg-gray-100 aspect-square">
                                @if($relatedProduct->primaryImage)
                                    <img src="{{ asset('storage/' . $relatedProduct->primaryImage->image_url) }}" 
                                         alt="{{ $relatedProduct->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $relatedProduct->name }}</h3>
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-bold text-purple-600">₱{{ number_format($relatedProduct->base_price, 2) }}</span>
                                    <a href="{{ route('products.show', $relatedProduct) }}" 
                                       class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-customer-layout>
