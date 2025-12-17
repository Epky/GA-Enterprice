<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'G&A Beauty Store') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('storage/logo/G&A_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('storage/logo/G&A_logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 shadow-xl sticky top-0 z-50" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3 hover:opacity-80 transition-opacity">
                        <div class="h-10 w-10 rounded-full overflow-hidden bg-white shadow-md ring-2 ring-pink-200">
                            <img src="{{ asset('storage/logo/G&A_logo.png') }}" alt="G&A Beauty Store Logo" class="h-full w-full object-cover">
                        </div>
                        <h1 class="text-2xl font-bold text-white">
                            G&A Beauty Store
                        </h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Go to Admin Dashboard">
                        Admin Dashboard
                    </a>
                    @elseif(auth()->user()->role === 'customer')
                    <a href="{{ route('customer.dashboard') }}" class="text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Go to My Account">
                        My Account
                    </a>
                    @endif
                    @else
                    <a href="{{ route('login') }}" class="text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Login to your account">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="bg-white text-purple-600 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 transition-all duration-200" aria-label="Register for a new account">
                        Register
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-[#FFF0F5]" aria-labelledby="hero-heading">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-pink-100/50 to-transparent pointer-events-none"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-pink-200/30 blur-3xl mix-blend-multiply animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center min-h-[600px] py-12 md:py-0">

                <!-- Text Content (Left) -->
                <div class="text-left order-2 md:order-1 relative">
                    <!-- Leaf Decoration -->
                    <div class="absolute -top-16 -left-16 w-32 h-32 opacity-20 transform -rotate-45 pointer-events-none">
                        <svg viewBox="0 0 100 100" fill="currentColor" class="text-green-800">
                            <path d="M50 0 C20 0 0 20 0 50 C0 80 20 100 50 100 C80 100 100 80 100 50 C90 50 80 40 50 0 Z" />
                        </svg>
                    </div>

                    <span class="inline-block py-1 px-3 rounded-none bg-green-700 text-white text-xs font-bold tracking-widest uppercase mb-6 shadow-sm">
                        New Collection
                    </span>

                    <h1 id="hero-heading" class="text-5xl md:text-7xl font-serif font-bold text-gray-900 mb-6 leading-tight">
                        Define Your Own <br>
                        <span class="italic text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-600">Standard of Beauty</span>
                    </h1>

                    <p class="text-lg md:text-xl text-gray-600 mb-10 font-light leading-relaxed max-w-lg">
                        Discover a world where science meets nature. Curated premium cosmetics ensuring radiant skin for every unique glow.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @auth
                        <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('customer.dashboard') }}"
                            class="bg-pink-500 text-white px-10 py-4 rounded-full text-lg font-bold hover:bg-pink-600 transition-all duration-300 shadow-lg hover:shadow-pink-500/30 hover:-translate-y-1 text-center"
                            aria-label="Go to your dashboard">
                            Go to Dashboard
                        </a>
                        @else
                        <a href="{{ route('register') }}"
                            class="bg-pink-500 text-white px-10 py-4 rounded-full text-lg font-bold hover:bg-pink-600 transition-all duration-300 shadow-lg hover:shadow-pink-500/30 hover:-translate-y-1 text-center"
                            aria-label="Shop Now">
                            Shop Now
                        </a>
                        <a href="#featured-products"
                            class="bg-white text-gray-900 px-10 py-4 rounded-full text-lg font-bold hover:bg-gray-50 transition-all duration-300 border border-gray-200 hover:border-gray-300 shadow-md hover:-translate-y-1 text-center flex items-center justify-center gap-2 group"
                            aria-label="Read More">
                            Read More
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                        @endauth
                    </div>
                </div>

                <!-- Hero Image (Right) -->
                <div class="order-1 md:order-2 relative h-[500px] md:h-[700px] flex items-end justify-center md:justify-end">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#FFF0F5] via-transparent to-transparent z-10 h-32 bottom-0 w-full"></div>
                    <!-- Blob/Splash Background -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] bg-pink-200/30 rounded-full blur-3xl mix-blend-multiply pointer-events-none"></div>

                    <img src="{{ asset('storage/artifacts/hero_beauty_model.png') }}"
                        onerror="this.src='https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'"
                        alt="Model with glowing skin"
                        class="relative z-0 h-full w-auto object-cover object-center transform md:translate-x-8 hover:scale-105 transition-transform duration-700 ease-out mask-image-gradient">
                </div>

            </div>
        </div>
    </section>

    <!-- Brands/Trust Section -->
    <div class="bg-white/50 backdrop-blur-sm border-b border-gray-100 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-400 text-sm font-medium tracking-widest uppercase mb-6">Trusted by Beauty Professionals</p>
            <div class="flex flex-wrap justify-center gap-8 md:gap-16 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                <!-- Branding Mockups (Icons) -->
                <div class="flex items-center space-x-2 text-xl font-bold text-gray-400 hover:text-pink-500 transition-colors cursor-default">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                    </svg>
                    <span>Lumière</span>
                </div>
                <div class="flex items-center space-x-2 text-xl font-bold text-gray-400 hover:text-purple-500 transition-colors cursor-default">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                    </svg>
                    <span>Essence</span>
                </div>
                <div class="flex items-center space-x-2 text-xl font-bold text-gray-400 hover:text-indigo-500 transition-colors cursor-default">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <span>Timeless</span>
                </div>
                <div class="flex items-center space-x-2 text-xl font-bold text-gray-400 hover:text-pink-500 transition-colors cursor-default">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L7 4.414v3.758a1 1 0 01-.293.707l-4 4C.817 14.761 2.165 17.5 4.8 17.5h10.4c2.635 0 3.983-2.74 2.093-4.621l-4-4A1 1 0 0113 8.172V4.414l.707-.707A1 1 0 0013 2H7zM5 8.172v-3.35l.95-.95a1 1 0 00-1.9 0l.95.95z" clip-rule="evenodd" />
                    </svg>
                    <span>Alchemy</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Products Section -->
    <section id="featured-products" class="py-20 bg-gray-50" aria-labelledby="products-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div class="max-w-2xl">
                    <span class="text-purple-600 font-semibold tracking-wider text-sm uppercase">Curated For You</span>
                    <h3 id="products-heading" class="text-4xl font-bold text-gray-900 mt-2">Featured Collection</h3>
                    <p class="text-gray-600 mt-4 text-lg">Handpicked essentials that are trending now.</p>
                </div>
                <a href="{{ route('products.index') }}" class="hidden md:flex items-center text-purple-600 font-semibold hover:text-purple-800 transition-colors group">
                    View All Products
                    <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>

            @if(isset($products) && $products->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach($products as $product)
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden border border-gray-100 flex flex-col h-full">
                    <!-- Product Image -->
                    <div class="relative h-64 overflow-hidden bg-gray-100">
                        @if($product->primaryImage)
                        <img src="{{ $product->primaryImage->full_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-200">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif

                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            @if($product->is_new_arrival)
                            <span class="bg-black text-white text-xs font-bold px-3 py-1 rounded-full">NEW</span>
                            @endif
                            @if($product->sale_price)
                            <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">SALE</span>
                            @endif
                        </div>

                        <!-- Overlay Action -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <a href="{{ route('products.show', $product) }}" class="bg-white text-gray-900 px-6 py-2 rounded-full font-bold transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 hover:bg-gray-100">
                                View Details
                            </a>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-6 flex-grow flex flex-col">
                        <div class="text-xs text-gray-500 mb-2 uppercase tracking-wide">{{ $product->category->name ?? 'Uncategorized' }}</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2 line-clamp-1 group-hover:text-purple-600 transition-colors">
                            <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                        </h4>

                        <div class="mt-auto pt-4 flex items-center justify-between border-t border-gray-50">
                            <div class="flex flex-col">
                                @if($product->sale_price)
                                <span class="text-red-500 font-bold text-lg">₱{{ number_format($product->sale_price, 2) }}</span>
                                <span class="text-gray-400 text-sm line-through">₱{{ number_format($product->base_price, 2) }}</span>
                                @else
                                <span class="text-gray-900 font-bold text-lg">₱{{ number_format($product->base_price, 2) }}</span>
                                @endif
                            </div>

                            @auth
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center hover:bg-purple-600 hover:text-white transition-colors" title="Add to Cart">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </button>
                            </form>
                            @else
                            <div class="group/tooltip relative">
                                <button disabled class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </button>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-900 rounded opacity-0 group-hover/tooltip:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none">
                                    Login to Buy
                                </span>
                            </div>
                            @endauth
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
                <p class="text-gray-500 text-lg">No products available at the moment.</p>
            </div>
            @endif

            <div class="mt-12 text-center md:hidden">
                <a href="{{ route('products.index') }}" class="inline-block bg-white text-purple-600 font-bold border border-purple-200 px-8 py-3 rounded-full hover:bg-purple-50 transition-colors">
                    View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us / Features Section -->
    <section class="py-20 bg-white" aria-labelledby="features-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-pink-500 font-semibold tracking-wider text-sm uppercase">The G&A Difference</span>
                <h3 id="features-heading" class="text-3xl md:text-4xl font-bold text-gray-900 mt-2">Why Choose Us</h3>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <!-- Feature 1 -->
                <div class="text-center group">
                    <div class="w-20 h-20 bg-pink-50 rounded-2xl rotate-3 mx-auto mb-8 flex items-center justify-center group-hover:rotate-6 transition-transform duration-300 group-hover:bg-pink-100">
                        <svg class="w-10 h-10 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Authentic icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-4">100% Authentic</h4>
                    <p class="text-gray-600 leading-relaxed">Sourced directly from authorized distributors. We guarantee the authenticity of every product we sell.</p>
                </div>

                <!-- Feature 2 -->
                <div class="text-center group md:transform md:-translate-y-4">
                    <div class="w-20 h-20 bg-purple-50 rounded-2xl -rotate-3 mx-auto mb-8 flex items-center justify-center group-hover:rotate-0 transition-transform duration-300 group-hover:bg-purple-100">
                        <svg class="w-10 h-10 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Delivery icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-4">Express Delivery</h4>
                    <p class="text-gray-600 leading-relaxed">Swift and secure shipping to ensure your beauty essentials arrive when you need them most.</p>
                </div>

                <!-- Feature 3 -->
                <div class="text-center group">
                    <div class="w-20 h-20 bg-indigo-50 rounded-2xl rotate-3 mx-auto mb-8 flex items-center justify-center group-hover:rotate-6 transition-transform duration-300 group-hover:bg-indigo-100">
                        <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Support icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-4">Expert Support</h4>
                    <p class="text-gray-600 leading-relaxed">Our beauty advisors are here to help you find the perfect products for your skin type and concerns.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section (Compact) -->
    <section class="py-20 bg-gray-900 text-white" aria-labelledby="categories-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-12">
                <div>
                    <h3 id="categories-heading" class="text-3xl font-bold mb-2">Shop by Category</h3>
                    <p class="text-gray-400">Explore our comprehensive range</p>
                </div>
                <a href="{{ route('products.index') }}" class="mt-4 md:mt-0 text-pink-400 hover:text-pink-300 font-medium transition-colors">See All Categories &rarr;</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @forelse($categories->take(4) as $category)
                <a href="{{ route('products.category', $category) }}"
                    class="group relative overflow-hidden rounded-2xl aspect-square bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">

                    <!-- Category Image (If available) -->
                    @if($category->image_url)
                    <div class="absolute inset-0 z-0">
                        <img src="{{ Storage::url($category->image_url) }}"
                            alt="{{ $category->name }}"
                            class="w-full h-full object-cover opacity-60 group-hover:opacity-100 group-hover:scale-110 transition-all duration-700">
                        <div class="absolute inset-0 bg-black/60 group-hover:bg-black/40 transition-colors duration-300"></div>
                    </div>
                    @else
                    <!-- Decorative Circle (Fallback) -->
                    <div class="absolute -bottom-10 -right-10 w-32 h-32 rounded-full bg-gradient-to-br from-pink-500/20 to-purple-500/20 blur-xl group-hover:scale-150 transition-transform duration-500"></div>
                    @endif

                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                        @if(!$category->image_url)
                        <div class="mb-4 group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                            @if(Str::contains(strtolower($category->name), ['skin', 'lotion', 'cream']))
                            <!-- Skin/Lotion Icon -->
                            <svg class="w-12 h-12 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            @elseif(Str::contains(strtolower($category->name), ['face', 'mask']))
                            <!-- Face/Glow Icon -->
                            <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @elseif(Str::contains(strtolower($category->name), 'lip'))
                            <!-- Lips/Heart Icon -->
                            <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            @elseif(Str::contains(strtolower($category->name), 'eye'))
                            <!-- Eye Icon -->
                            <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            @else
                            <!-- Default Sparkle Icon -->
                            <svg class="w-12 h-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                            @endif
                        </div>
                        @endif
                        <h4 class="font-bold text-lg mb-1 drop-shadow-md">{{ $category->name }}</h4>
                        <span class="text-xs text-gray-300 group-hover:text-white transition-colors drop-shadow-sm">
                            {{ $category->active_products_count ?? 0 }} Products
                        </span>
                    </div>
                </a>
                @empty
                <div class="col-span-4 text-center py-8 text-gray-500">
                    No categories available.
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 py-24 overflow-hidden" aria-labelledby="cta-heading">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h3 id="cta-heading" class="text-4xl md:text-5xl font-bold text-white mb-6">Ready to Transform Your Look?</h3>
            <p class="text-xl text-white/90 mb-10 leading-relaxed">Join our community of beauty enthusiasts and get exclusive access to new arrivals, special offers, and beauty tips.</p>
            @guest
            <a href="{{ route('register') }}"
                class="inline-block bg-white text-purple-600 px-10 py-4 rounded-full text-lg font-bold hover:bg-gray-900 hover:text-white focus:outline-none focus:ring-4 focus:ring-purple-900/50 transition-all duration-300 shadow-2xl hover:shadow-lg hover:-translate-y-1"
                aria-label="Create your account to get started">
                Join G&A Beauty Now
            </a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900 text-white py-12 border-t-4 border-pink-500" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Desktop: 3-column layout, Mobile: stacked -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Column 1: Brand -->
                <div class="text-center md:text-left">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-pink-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent mb-3">
                        G&A Beauty Store
                    </h3>
                    <p class="text-purple-200 text-sm">Your destination for premium beauty products</p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="text-center">
                    <h4 class="text-lg font-semibold text-white mb-3">Quick Links</h4>
                    <nav class="flex flex-col space-y-2" aria-label="Footer navigation">
                        <a href="#" class="text-purple-300 hover:text-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-900 rounded transition-colors duration-200 inline-block" aria-label="About us">About Us</a>
                        <a href="#" class="text-purple-300 hover:text-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-900 rounded transition-colors duration-200 inline-block" aria-label="Contact us">Contact</a>
                        <a href="#" class="text-purple-300 hover:text-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-900 rounded transition-colors duration-200 inline-block" aria-label="Privacy policy">Privacy Policy</a>
                        <a href="#" class="text-purple-300 hover:text-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-900 rounded transition-colors duration-200 inline-block" aria-label="Terms of service">Terms of Service</a>
                    </nav>
                </div>

                <!-- Column 3: Contact Info -->
                <div class="text-center md:text-right">
                    <h4 class="text-lg font-semibold text-white mb-3">Get in Touch</h4>
                    <div class="text-purple-200 text-sm space-y-2">
                        <p>📧 payanedsel26@gmail.com</p>
                        <p>📱 09913615463</p>
                        <p>📍 Digos City Davao Del. Sur, Philippines</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-purple-700 pt-6 text-center">
                <p class="text-purple-400 text-sm">&copy; {{ date('Y') }} G&A Beauty Store. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>