<x-customer-layout>
    <!-- Hero Section with Search -->
    <div class="relative overflow-hidden bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-20">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-pink-400 blur-3xl mix-blend-screen animate-pulse"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-purple-400 blur-3xl mix-blend-screen animate-pulse animation-delay-2000"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-indigo-400 blur-3xl mix-blend-screen animate-pulse animation-delay-4000"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-24">
            <div class="text-center max-w-4xl mx-auto">
                <span class="inline-block py-1 px-3 rounded-full bg-white/20 text-white text-sm font-semibold tracking-wider mb-6 backdrop-blur-sm border border-white/20 shadow-sm">
                    ✨ WELCOME BACK
                </span>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold mb-6 tracking-tight leading-tight drop-shadow-lg">
                    Hello, {{ Auth::user()->name }}!
                </h1>
                <p class="text-xl md:text-2xl mb-10 text-pink-50 font-light leading-relaxed max-w-2xl mx-auto">
                    Discover premium beauty products curated for your perfect look.
                </p>

                <!-- Search Bar -->
                <form method="GET" action="{{ route('customer.dashboard') }}" class="max-w-3xl mx-auto" role="search" aria-label="Product search">
                    <div class="flex flex-col sm:flex-row gap-2 bg-white/10 p-2 rounded-3xl backdrop-blur-md border border-white/20 shadow-2xl">
                        <label for="search-input" class="sr-only">Search for products, brands, or categories</label>
                        <input type="text"
                            id="search-input"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search for products, brands, categories..."
                            aria-label="Search for products, brands, or categories"
                            class="flex-1 px-6 py-4 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-0 border-none bg-white rounded-2xl sm:rounded-r-none h-[56px]">
                        <button type="submit" aria-label="Submit search" class="px-8 py-3 bg-white text-purple-700 font-bold hover:bg-gray-50 transition-all duration-300 rounded-2xl sm:rounded-l-none h-[56px] flex items-center justify-center gap-2 shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span class="hidden sm:inline">Search</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Featured Products -->
        @if($featuredProducts->count() > 0)
        <section class="mb-16" aria-labelledby="featured-products-heading">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 id="featured-products-heading" class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">✨ Featured Products</h2>
                    <p class="text-gray-600">Handpicked selections just for you</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                @foreach($featuredProducts as $product)
                <article class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group" aria-label="Featured product: {{ $product->name }}">
                    <div class="relative overflow-hidden bg-gray-100 aspect-square">
                        @if($product->primaryImage)
                        <img src="{{ asset('storage/' . $product->primaryImage->image_url) }}"
                            alt="{{ $product->name }} - Product image"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                        <div class="w-full h-full flex items-center justify-center" role="img" aria-label="No image available for {{ $product->name }}">
                            <svg class="h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif
                        <div class="absolute top-3 right-3">
                            <span class="bg-gradient-to-r from-yellow-400 to-orange-400 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg" role="status" aria-label="Featured product">
                                ⭐ FEATURED
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-purple-600 font-semibold mb-1 uppercase tracking-wide">{{ $product->category->name ?? 'Uncategorized' }}</p>
                        <h3 class="font-semibold text-gray-900 mb-3 line-clamp-2 min-h-[2.5rem]">{{ $product->name }}</h3>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xl lg:text-2xl font-bold text-purple-600" aria-label="Price: {{ number_format($product->base_price, 2) }} pesos">₱{{ number_format($product->base_price, 2) }}</span>
                            <a href="{{ route('products.show', $product) }}"
                                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors text-sm font-medium min-h-[44px] min-w-[44px] flex items-center justify-center"
                                aria-label="View details for {{ $product->name }}">
                                View
                            </a>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endif

        <!-- Filters and Products Grid -->
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">

            <!-- Sidebar Filters -->
            <aside class="w-full lg:w-72 flex-shrink-0 order-1 lg:order-none" aria-label="Product filters">
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 lg:sticky lg:top-4">
                    <div class="flex items-center gap-2 mb-6">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-900">Filters</h3>
                    </div>

                    <form method="GET" action="{{ route('customer.dashboard') }}" id="filter-form" aria-label="Filter products">
                        <!-- Keep search term -->
                        @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif

                        <!-- Error Messages -->
                        @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl" role="alert" aria-live="assertive">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-red-800 mb-1">Filter Error</h4>
                                    <ul class="text-sm text-red-700 space-y-1">
                                        @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label for="category-filter" class="block text-sm font-semibold text-gray-700 mb-3">Category</label>
                            <select id="category-filter" name="category" class="w-full rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm min-h-[48px] transition-all" onchange="document.getElementById('filter-form').submit()" aria-label="Filter by category">
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
                            <label for="brand-filter" class="block text-sm font-semibold text-gray-700 mb-3">Brand</label>
                            <select id="brand-filter" name="brand" class="w-full rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm min-h-[48px] transition-all" onchange="document.getElementById('filter-form').submit()" aria-label="Filter by brand">
                                <option value="">All Brands</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Price Range -->
                        <fieldset class="mb-6">
                            <legend class="block text-sm font-semibold text-gray-700 mb-3">Price Range</legend>
                            <div class="flex gap-3">
                                <label for="min-price" class="sr-only">Minimum price</label>
                                <input type="number"
                                    id="min-price"
                                    name="min_price"
                                    value="{{ request('min_price') }}"
                                    placeholder="Min"
                                    aria-label="Minimum price"
                                    @if($errors->has('min_price') || $errors->has('price')) aria-invalid="true" aria-describedby="price-error" @endif
                                class="w-1/2 rounded-xl border-2 {{ $errors->has('min_price') || $errors->has('price') ? 'border-red-300' : 'border-gray-200' }} focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm min-h-[48px] transition-all">
                                <label for="max-price" class="sr-only">Maximum price</label>
                                <input type="number"
                                    id="max-price"
                                    name="max_price"
                                    value="{{ request('max_price') }}"
                                    placeholder="Max"
                                    aria-label="Maximum price"
                                    @if($errors->has('max_price') || $errors->has('price')) aria-invalid="true" aria-describedby="price-error" @endif
                                class="w-1/2 rounded-xl border-2 {{ $errors->has('max_price') || $errors->has('price') ? 'border-red-300' : 'border-gray-200' }} focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm min-h-[48px] transition-all">
                            </div>
                            @if($errors->has('price'))
                            <p id="price-error" class="mt-2 text-sm text-red-600" role="alert">{{ $errors->first('price') }}</p>
                            @endif
                            <button type="submit" class="mt-3 w-full px-4 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all text-sm font-semibold min-h-[48px] shadow-md hover:shadow-lg" aria-label="Apply price range filters">
                                Apply Filters
                            </button>
                        </fieldset>

                        <!-- Clear Filters -->
                        @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'search']))
                        <a href="{{ route('customer.dashboard') }}" class="block w-full text-center px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all text-sm font-semibold min-h-[48px] flex items-center justify-center gap-2" aria-label="Clear all filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear All Filters
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1 order-2 lg:order-none" aria-label="Product listing">
                <!-- Sort and Results Count -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-4 sm:p-6 mb-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div role="status" aria-live="polite">
                            <p class="text-sm text-gray-600">
                                Showing <span class="font-bold text-purple-600">{{ $products->firstItem() ?? 0 }}</span>
                                to <span class="font-bold text-purple-600">{{ $products->lastItem() ?? 0 }}</span>
                                of <span class="font-bold text-purple-600">{{ $products->total() }}</span> products
                            </p>
                        </div>

                        <form method="GET" action="{{ route('customer.dashboard') }}" class="flex items-center gap-3" aria-label="Sort products">
                            <!-- Preserve filters -->
                            @foreach(request()->except('sort') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <label for="sort-select" class="text-sm text-gray-600 font-medium whitespace-nowrap">Sort by:</label>
                            <select id="sort-select" name="sort" onchange="this.form.submit()" class="rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm min-h-[44px] px-4 transition-all" aria-label="Sort products by">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name: A-Z</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-8" role="list" aria-label="Products">
                    @foreach($products as $product)
                    <article class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group border border-gray-100" role="listitem" aria-label="Product: {{ $product->name }}">
                        <div class="relative overflow-hidden bg-gray-100 aspect-square">
                            @if($product->primaryImage)
                            <img src="{{ asset('storage/' . $product->primaryImage->image_url) }}"
                                alt="{{ $product->name }} - Product image"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100" role="img" aria-label="No image available for {{ $product->name }}">
                                <svg class="h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            @endif
                            @php
                            $totalStock = $product->inventory->sum('quantity_available');
                            @endphp
                            @if($totalStock <= 0)
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center backdrop-blur-sm">
                                <span class="bg-red-500 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg" role="status" aria-label="Out of stock">OUT OF STOCK</span>
                        </div>
                        @endif
                </div>
                <div class="p-4 lg:p-5">
                    <p class="text-xs text-purple-600 font-semibold mb-1 uppercase tracking-wide">{{ $product->category->name ?? 'Uncategorized' }}</p>
                    <h3 class="font-semibold text-base text-gray-900 mb-2 line-clamp-2 min-h-[3rem]">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2 min-h-[2.5rem]">{{ Str::limit($product->description, 80) }}</p>
                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100">
                        <span class="text-xl lg:text-2xl font-bold text-purple-600" aria-label="Price: {{ number_format($product->base_price, 2) }} pesos">₱{{ number_format($product->base_price, 2) }}</span>
                        <a href="{{ route('products.show', $product) }}"
                            class="px-4 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all text-sm font-semibold min-h-[44px] min-w-[44px] flex items-center justify-center whitespace-nowrap shadow-md hover:shadow-lg"
                            aria-label="View details for {{ $product->name }}">
                            View Details
                        </a>
                    </div>
                </div>
                </article>
                @endforeach
        </div>

        <!-- Pagination -->
        <nav class="mt-6 sm:mt-8" aria-label="Pagination">
            {{ $products->appends(request()->query())->links() }}
        </nav>
        @else
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-12 sm:p-16 text-center" role="status" aria-live="polite">
            <div class="max-w-md mx-auto">
                <div class="bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6" aria-hidden="true">
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">No products found</h3>
                <p class="text-base text-gray-600 mb-6">We couldn't find any products matching your criteria. Try adjusting your filters or search terms.</p>
                <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all text-base font-semibold min-h-[48px] shadow-md hover:shadow-lg" aria-label="View all products">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    View All Products
                </a>
            </div>
        </div>
        @endif
        </main>
    </div>
    </div>
</x-customer-layout>