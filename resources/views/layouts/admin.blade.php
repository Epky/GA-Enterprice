<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'G&A') }} - Admin Panel</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('storage/logo/G&A_logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/logo/G&A_logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gradient-to-br from-pink-50 via-purple-50 to-indigo-50">
            <!-- Admin Navigation -->
            <nav x-data="{ open: false }" class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 shadow-xl backdrop-blur-md relative z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                                    <img src="{{ asset('storage/logo/G&A_logo.png') }}" alt="G&A Beauty Store" class="h-12 w-12 rounded-full object-cover shadow-lg">
                                    <span class="text-xl font-bold bg-gradient-to-r from-pink-200 via-purple-200 to-indigo-200 bg-clip-text text-transparent">Admin</span>
                                </a>
                            </div>

                            <!-- Admin Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('dashboard') ? 'border-b-2 border-white' : '' }}">
                                    {{ __('Dashboard') }}
                                </a>
                            </div>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white/90 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-500 transition-all duration-200">
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
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white/90 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-purple-500 transition-all duration-200">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/10 backdrop-blur-md">
                    <div class="pt-2 pb-3 space-y-1">
                        <!-- Dashboard Section -->
                        <div class="px-3 py-2">
                            <p class="text-xs font-semibold text-white/60 uppercase tracking-wider">Dashboard</p>
                        </div>
                        
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.dashboard') && !request()->routeIs('admin.dashboard.*') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ __('Overview') }}
                        </a>
                        
                        <a href="{{ route('admin.dashboard.sales') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.dashboard.sales') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Sales & Revenue') }}
                        </a>
                        
                        <a href="{{ route('admin.dashboard.customers') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.dashboard.customers') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            {{ __('Customers & Channels') }}
                        </a>
                        
                        <a href="{{ route('admin.dashboard.inventory') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.dashboard.inventory') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            {{ __('Inventory Insights') }}
                        </a>
                        
                        <!-- Management Section -->
                        <div class="px-3 py-2 mt-4">
                            <p class="text-xs font-semibold text-white/60 uppercase tracking-wider">Management</p>
                        </div>
                        
                        <a href="{{ route('admin.users.index') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                            {{ __('Users') }}
                        </a>
                        
                        <a href="{{ route('admin.staff.index') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.staff.*') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ __('Staff') }}
                        </a>
                        
                        <a href="{{ route('admin.products.index') }}" class="flex items-center pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'border-l-4 border-white bg-white/10' : '' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            {{ __('Products') }}
                        </a>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-white/20">
                        <div class="px-4 flex items-center">
                            <x-user-avatar :user="Auth::user()" size="md" class="mr-3" />
                            <div>
                                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                                <div class="font-medium text-sm text-white/80">{{ Auth::user()->email }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <a href="{{ route('profile.edit') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200">
                                {{ __('Profile') }}
                            </a>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();"
                                        class="block pl-3 pr-4 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-md transition-all duration-200">
                                    {{ __('Log Out') }}
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Admin Sidebar and Content -->
            <div class="flex">
                <!-- Sidebar -->
                <aside class="w-64 bg-gradient-to-b from-pink-50 via-purple-50 to-indigo-50 border-r border-purple-200 min-h-screen">
                    <nav class="mt-5 px-2">
                        <!-- Dashboard Section -->
                        <div class="mb-6">
                            <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Dashboard</h3>
                            
                            <a href="{{ route('admin.dashboard') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.dashboard') && !request()->routeIs('admin.dashboard.*') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.dashboard') && !request()->routeIs('admin.dashboard.*') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Overview
                            </a>
                            
                            <a href="{{ route('admin.dashboard.sales') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.dashboard.sales') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.dashboard.sales') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Sales & Revenue
                            </a>
                            
                            <a href="{{ route('admin.dashboard.customers') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.dashboard.customers') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.dashboard.customers') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Customers & Channels
                            </a>
                            
                            <a href="{{ route('admin.dashboard.inventory') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.dashboard.inventory') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.dashboard.inventory') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Inventory Insights
                            </a>
                        </div>
                        
                        <!-- Management Section -->
                        <div class="mb-6">
                            <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Management</h3>
                            
                            <a href="{{ route('admin.users.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.users.*') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                                Users
                            </a>
                            
                            <a href="{{ route('admin.staff.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.staff.*') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.staff.*') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Staff
                            </a>
                            
                            <a href="{{ route('admin.products.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.products.*') ? 'bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-100 hover:to-purple-100 hover:text-purple-700' }} transition-all duration-200">
                                <svg class="mr-4 h-6 w-6 {{ request()->routeIs('admin.products.*') ? 'text-purple-700' : 'text-purple-500 group-hover:text-purple-700' }} transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Products
                            </a>
                        </div>

                        @yield('sidebar-links')
                    </nav>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 bg-gradient-to-br from-pink-50 via-purple-50 to-indigo-50 min-h-screen">
                    <!-- Page Heading -->
                    @isset($header)
                        <header class="bg-white/80 backdrop-blur-sm shadow-md">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Page Content -->
                    <div class="py-12">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            @if (session('success'))
                                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                    <span class="block sm:inline">{{ session('success') }}</span>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                    <span class="block sm:inline">{{ session('error') }}</span>
                                </div>
                            @endif

                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        @stack('scripts')
    </body>
</html>
