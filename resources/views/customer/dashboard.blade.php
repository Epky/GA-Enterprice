<x-customer-layout>
    <!-- Hero Section with Search -->
    <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">Welcome to GA Beauty Store</h1>
                <p class="text-xl mb-8 text-pink-100">Discover premium beauty products for your perfect look</p>
                
                <!-- Search Bar -->
                <form method="GET" action="{{ route('customer.dashboard') }}" class="max-w-2xl mx-auto">
                    <div class="flex gap-2">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Search for products..." 
                               class="flex-1 px-6 py-4 rounded-full text-gray-900 focus:outline-none focus:ring-4 focus:ring-pink-300">
                        <button type="submit" class="px-8 py-4 bg-white text-purple-600 rounded-full font-semibold hover:bg-pink-50 transition-colors">
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Featured Products -->
        @if($featuredProducts->count() > 0)
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">✨ Featured Products</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300 group">
                        <div class="relative overflow-hidden bg-gray-100 aspect-square">
                            @if($product->primaryImage)
                                <img src="{{ asset('storage/' . $product->primaryImage->image_url) }}" 
                                     alt="{{ $product->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div class="absolute top-3 right-3">
                                <span class="bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-bold">FEATURED</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-gray-500 mb-1">{{ $product->category->name ?? 'Uncategorized' }}</p>
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $product->name }}</h3>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-purple-600">₱{{ number_format($product->base_price, 2) }}</span>
                                <a href="{{ route('customer.product.show', $product) }}" 
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

        <!-- Filters and Products Grid -->
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Filters -->
            <div class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET" action="{{ route('customer.dashboard') }}" id="filter-form">
                        <!-- Keep search term -->
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500" onchange="document.getElementById('filter-form').submit()">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Brand Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select name="brand" class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500" onchange="document.getElementById('filter-form').submit()">
                                <option value="">All Brands</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" class="w-1/2 rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" class="w-1/2 rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                            </div>
                            <button type="submit" class="mt-2 w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                Apply
                            </button>
                        </div>

                        <!-- Clear Filters -->
                        @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'search']))
                            <a href="{{ route('customer.dashboard') }}" class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                                Clear Filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1">
                <!-- Sort and Results Count -->
                <div class="flex justify-between items-center mb-6">
                    <p class="text-gray-600">
                        Showing <span class="font-semibold">{{ $products->firstItem() ?? 0 }}</span> 
                        to <span class="font-semibold">{{ $products->lastItem() ?? 0 }}</span> 
                        of <span class="font-semibold">{{ $products->total() }}</span> products
                    </p>
                    
                    <form method="GET" action="{{ route('customer.dashboard') }}" class="flex items-center gap-2">
                        <!-- Preserve filters -->
                        @foreach(request()->except('sort') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        
                        <label class="text-sm text-gray-600">Sort by:</label>
                        <select name="sort" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name: A-Z</option>
                        </select>
                    </form>
                </div>

                <!-- Products Grid -->
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        @foreach($products as $product)
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300 group">
                                <div class="relative overflow-hidden bg-gray-100 aspect-square">
                                    @if($product->primaryImage)
                                        <img src="{{ asset('storage/' . $product->primaryImage->image_url) }}" 
                                             alt="{{ $product->name }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    @php
                                        $totalStock = $product->inventory->sum('quantity_available');
                                    @endphp
                                    @if($totalStock <= 0)
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-4 py-2 rounded-lg font-bold">OUT OF STOCK</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <p class="text-xs text-gray-500 mb-1">{{ $product->category->name ?? 'Uncategorized' }}</p>
                                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 h-12">{{ $product->name }}</h3>
                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ Str::limit($product->description, 60) }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold text-purple-600">₱{{ number_format($product->base_price, 2) }}</span>
                                        <a href="{{ route('customer.product.show', $product) }}" 
                                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                        <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your filters or search terms</p>
                        <a href="{{ route('customer.dashboard') }}" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            View All Products
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-customer-layout>
