<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'G&A') }} - Login</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('storage/logo/G&A_logo.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex text-gray-900">
        <!-- Left Section: Authentication Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white relative">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <!-- Branding -->
                <div class="text-center mb-10">
                    <a href="/" class="inline-block relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 rounded-full blur opacity-50 group-hover:opacity-100 transition duration-500"></div>
                        <img src="{{ asset('storage/logo/G&A_logo.png') }}" alt="G&A Beauty Store" class="h-24 w-24 rounded-full object-cover relative ring-4 ring-white shadow-xl">
                    </a>
                    <h2 class="mt-6 text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600 font-serif">
                        Welcome Back
                    </h2>
                    <p class="mt-2 text-sm text-gray-500">
                        Please sign in to access your dashboard
                    </p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <!-- Success Message -->
                @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-100 flex items-center gap-3">
                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="font-medium text-sm text-green-700">{{ session('success') }}</p>
                </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2 pl-1">Email address</label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm transition-all duration-200 bg-gray-50/50 focus:bg-white"
                                placeholder="you@example.com"
                                value="{{ old('email') }}">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs pl-1" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2 pl-1">Password</label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm transition-all duration-200 bg-gray-50/50 focus:bg-white"
                                placeholder="••••••••">
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs pl-1" />
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded transition-all">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-600">Remember me</label>
                        </div>

                        @if (Route::has('password.request'))
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" class="font-medium text-purple-600 hover:text-purple-500 transition-colors">
                                Forgot password?
                            </a>
                        </div>
                        @endif
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 hover:from-pink-600 hover:via-purple-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform hover:-translate-y-0.5 transition-all duration-200">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">
                                New to G&A Beauty?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 text-center space-y-4">
                        <a href="{{ route('register') }}" class="w-full inline-flex justify-center py-3.5 px-4 rounded-2xl shadow-sm bg-white text-sm font-bold text-gray-700 border border-gray-200 hover:bg-gray-50 hover:text-purple-600 transition-all duration-200">
                            Create an account
                        </a>
                        <a href="{{ route('home') }}" class="block text-sm text-gray-400 hover:text-gray-600 transition-colors">
                            ← Back to Landing Page
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-12 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

        <!-- Right Section: Decorative Image -->
        <div class="hidden lg:block relative w-0 flex-1 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-pink-400/20 to-purple-500/20 z-10 mix-blend-multiply"></div>
            <img class="absolute inset-0 h-full w-full object-cover"
                src="https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
                alt="Beauty concept background">

            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent z-20 flex flex-col justify-end p-20">
                <blockquote class="text-white space-y-6 max-w-lg mb-12">
                    <div class="h-1 w-20 bg-pink-500 rounded-full mb-6"></div>
                    <p class="text-4xl font-serif font-bold leading-tight">
                        "The most beautiful makeup of a woman is passion. But cosmetics are easier to buy."
                    </p>
                    <footer class="text-pink-200 font-medium text-lg">— Apol Leonida </footer>
                </blockquote>
            </div>
        </div>
    </div>
</body>

</html>