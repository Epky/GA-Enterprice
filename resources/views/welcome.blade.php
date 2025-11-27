<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'G&A Beauty Store') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-md shadow-xl sticky top-0 z-50" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 bg-clip-text text-transparent">
                        G&A Beauty Store
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-pink-600 hover:bg-pink-50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Go to Admin Dashboard">
                                Admin Dashboard
                            </a>
                        @elseif(auth()->user()->role === 'customer')
                            <a href="{{ route('customer.dashboard') }}" class="text-gray-700 hover:text-pink-600 hover:bg-pink-50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Go to My Account">
                                My Account
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-pink-600 hover:bg-pink-50 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200" aria-label="Login to your account">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200" aria-label="Register for a new account">
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500" aria-labelledby="hero-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h2 id="hero-heading" class="text-5xl md:text-6xl font-bold text-white mb-6">
                    Discover Your Natural Beauty
                </h2>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    Premium beauty products and cosmetics for every skin type. 
                    Enhance your natural glow with our curated collection.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    @auth
                        <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('customer.dashboard') }}" 
                           class="bg-white text-purple-600 px-8 py-3 rounded-lg text-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 transition shadow-xl"
                           aria-label="Go to your dashboard">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" 
                           class="bg-white text-purple-600 px-8 py-3 rounded-lg text-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 transition shadow-xl"
                           aria-label="Get started by creating an account">
                            Get Started
                        </a>
                        <a href="{{ route('login') }}" 
                           class="bg-transparent text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 transition border-2 border-white"
                           aria-label="Sign in to your existing account">
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-white py-16" aria-labelledby="features-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h3 id="features-heading" class="text-3xl font-bold text-gray-900 mb-4">Why Choose Us</h3>
                <p class="text-gray-600">Experience the best in beauty and skincare</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white text-center p-6 rounded-lg hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:bg-gradient-to-br hover:from-pink-50 hover:to-purple-50">
                    <div class="w-16 h-16 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg" aria-hidden="true">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Checkmark icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Premium Quality</h4>
                    <p class="text-gray-600">Only the finest beauty products from trusted brands</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white text-center p-6 rounded-lg hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:bg-gradient-to-br hover:from-pink-50 hover:to-purple-50">
                    <div class="w-16 h-16 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg" aria-hidden="true">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Clock icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Fast Delivery</h4>
                    <p class="text-gray-600">Quick and reliable shipping to your doorstep</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white text-center p-6 rounded-lg hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:bg-gradient-to-br hover:from-pink-50 hover:to-purple-50">
                    <div class="w-16 h-16 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg" aria-hidden="true">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Heart icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Customer Care</h4>
                    <p class="text-gray-600">Dedicated support for all your beauty needs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-gradient-to-br from-purple-50 to-pink-50" aria-labelledby="categories-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h3 id="categories-heading" class="text-3xl font-bold text-gray-900 mb-4">Shop by Category</h3>
                <p class="text-gray-600">Explore our wide range of beauty products</p>
            </div>
            
            <div class="grid md:grid-cols-4 gap-6" role="list">
                @forelse($categories as $index => $category)
                    @php
                        // Define color schemes for categories
                        $colorSchemes = [
                            ['border' => 'pink-300', 'gradient' => 'pink-50'],
                            ['border' => 'purple-300', 'gradient' => 'purple-50'],
                            ['border' => 'indigo-300', 'gradient' => 'indigo-50'],
                            ['border' => 'pink-300', 'gradient' => 'pink-50'],
                        ];
                        $colors = $colorSchemes[$index % count($colorSchemes)];
                        
                        // Define default emojis for categories
                        $emojis = ['💄', '🧴', '💅', '🌸'];
                        $emoji = $emojis[$index % count($emojis)];
                    @endphp
                    
                    <a href="{{ route('products.category', $category->slug) }}" 
                       class="bg-white rounded-lg shadow-lg p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300 border-2 border-transparent hover:border-{{ $colors['border'] }} hover:bg-gradient-to-br hover:from-white hover:to-{{ $colors['gradient'] }} focus:outline-none focus:ring-2 focus:ring-{{ $colors['border'] }} focus:ring-offset-2 block"
                       role="listitem"
                       aria-label="Browse {{ $category->name }} products">
                        <div class="text-4xl mb-3 text-center" role="img" aria-label="{{ $category->name }} category">{{ $emoji }}</div>
                        <h4 class="font-semibold text-gray-900 mb-2 text-center">{{ $category->name }}</h4>
                        <p class="text-sm text-gray-600 text-center">{{ Str::limit($category->description, 50) }}</p>
                        <p class="text-xs text-gray-500 text-center mt-2">{{ $category->active_products_count ?? 0 }} {{ Str::plural('product', $category->active_products_count ?? 0) }}</p>
                    </a>
                @empty
                    <div class="col-span-4 text-center py-8">
                        <p class="text-gray-600">No categories available at the moment.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 py-16" aria-labelledby="cta-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h3 id="cta-heading" class="text-3xl font-bold text-white mb-4">Ready to Glow?</h3>
            <p class="text-xl text-white/90 mb-8">Join thousands of satisfied customers</p>
            @guest
                <a href="{{ route('register') }}" 
                   class="bg-white text-purple-600 px-8 py-3 rounded-lg text-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-purple-500 transition shadow-xl inline-block"
                   aria-label="Create your account to get started">
                    Create Your Account
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
                        <p>📧 info@gabeauty.com</p>
                        <p>📱 +63 123 456 7890</p>
                        <p>📍 Manila, Philippines</p>
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
