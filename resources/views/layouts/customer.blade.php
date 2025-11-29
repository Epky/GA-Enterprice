<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50">
            <!-- Customer Navigation -->
            <nav x-data="{ open: false }" class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="/">
                                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                                </a>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <x-nav-link :href="route('customer.dashboard')" :active="request()->routeIs('customer.dashboard')">
                                    {{ __('Home') }}
                                </x-nav-link>
                                <x-nav-link href="/products" :active="request()->is('products*')">
                                    {{ __('Products') }}
                                </x-nav-link>
                            </div>
                        </div>

                        <!-- Right Side Navigation -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                            <!-- Cart Icon -->
                            @auth
                                @php
                                    $userCart = \App\Models\Cart::where('user_id', auth()->id())->first();
                                    $cartItemCount = $userCart ? $userCart->total_items : 0;
                                @endphp
                                <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-gray-900">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    @if($cartItemCount > 0)
                                        <span class="absolute -top-2 -right-2 bg-pink-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $cartItemCount }}
                                        </span>
                                    @endif
                                </a>
                            @endauth

                            @auth
                                <!-- User Dropdown -->
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                            <x-user-avatar :user="Auth::user()" size="sm" class="mr-2" />
                                            <div>{{ Auth::user()->name }}</div>

                                            <div class="ms-1">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link href="/dashboard">
                                            {{ __('My Account') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link href="/orders">
                                            {{ __('My Orders') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('profile.edit')">
                                            {{ __('Profile') }}
                                        </x-dropdown-link>

                                        <!-- Authentication -->
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf

                                            <x-dropdown-link :href="route('logout')"
                                                    onclick="event.preventDefault();
                                                                this.closest('form').submit();">
                                                {{ __('Log Out') }}
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            @else
                                <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900">Login</a>
                                <a href="{{ route('register') }}" class="text-sm text-gray-700 hover:text-gray-900">Register</a>
                            @endauth
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <x-responsive-nav-link :href="route('customer.dashboard')" :active="request()->routeIs('customer.dashboard')">
                            {{ __('Home') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link href="/products" :active="request()->is('products*')">
                            {{ __('Products') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link href="/cart">
                            {{ __('Cart') }}
                        </x-responsive-nav-link>
                    </div>

                    @auth
                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200">
                            <div class="px-4 flex items-center">
                                <x-user-avatar :user="Auth::user()" size="md" class="mr-3" />
                                <div>
                                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <x-responsive-nav-link href="/dashboard">
                                    {{ __('My Account') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link href="/orders">
                                    {{ __('My Orders') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-responsive-nav-link>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-responsive-nav-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-responsive-nav-link>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="pt-4 pb-1 border-t border-gray-200">
                            <div class="mt-3 space-y-1">
                                <x-responsive-nav-link :href="route('login')">
                                    {{ __('Login') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('register')">
                                    {{ __('Register') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>
                    @endauth
                </div>
            </nav>

            <!-- Page Content -->
            <main>
                @if (session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 mt-12">
                <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">About</h3>
                            <ul class="mt-4 space-y-2">
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">About Us</a></li>
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Contact</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Shop</h3>
                            <ul class="mt-4 space-y-2">
                                <li><a href="/products" class="text-base text-gray-500 hover:text-gray-900">All Products</a></li>
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Categories</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Support</h3>
                            <ul class="mt-4 space-y-2">
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Help Center</a></li>
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Shipping Info</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Legal</h3>
                            <ul class="mt-4 space-y-2">
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Privacy Policy</a></li>
                                <li><a href="#" class="text-base text-gray-500 hover:text-gray-900">Terms of Service</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <p class="text-base text-gray-400 text-center">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
