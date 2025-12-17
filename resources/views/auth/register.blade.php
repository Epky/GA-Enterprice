<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'G&A') }} - Register</title>
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
                        Create Account
                    </h2>
                    <p class="mt-2 text-sm text-gray-500">
                        Join us and unlock your true radiance
                    </p>
                </div>

                <!-- Register Form -->
                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2 pl-1">Full Name</label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input id="name" name="name" type="text" autocomplete="name" required autofocus
                                class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm transition-all duration-200 bg-gray-50/50 focus:bg-white"
                                placeholder="Jane Doe"
                                value="{{ old('name') }}">
                        </div>
                        <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-xs pl-1" />
                    </div>

                    <!-- Email -->
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

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2 pl-1">Password</label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm transition-all duration-200 bg-gray-50/50 focus:bg-white"
                                placeholder="••••••••">
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs pl-1" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2 pl-1">Confirm Password</label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                                class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm transition-all duration-200 bg-gray-50/50 focus:bg-white"
                                placeholder="••••••••">
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-xs pl-1" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 hover:from-pink-600 hover:via-purple-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform hover:-translate-y-0.5 transition-all duration-200">
                            Create Account
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
                                Already have an account?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 text-center space-y-4">
                        <a href="{{ route('login') }}" class="w-full inline-flex justify-center py-3.5 px-4 rounded-2xl shadow-sm bg-white text-sm font-bold text-gray-700 border border-gray-200 hover:bg-gray-50 hover:text-purple-600 transition-all duration-200">
                            Sign in instead
                        </a>
                        <a href="{{ route('home') }}" class="block text-sm text-gray-400 hover:text-gray-600 transition-colors">
                            ← Back to Landing Page
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-8 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

        <!-- Right Section: Decorative Image -->
        <div class="hidden lg:block relative w-0 flex-1 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-400/20 to-pink-500/20 z-10 mix-blend-multiply"></div>
            <img class="absolute inset-0 h-full w-full object-cover"
                src="https://images.unsplash.com/photo-1522337660859-02fbefca4702?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
                onerror="this.src='https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'"
                alt="Natural beauty concept">

            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent z-20 flex flex-col justify-end p-20">
                <blockquote class="text-white space-y-6 max-w-lg mb-12">
                    <div class="h-1 w-20 bg-purple-500 rounded-full mb-6"></div>
                    <p class="text-4xl font-serif font-bold leading-tight">
                        "Beauty begins the moment you decide to be yourself."
                    </p>
                    <footer class="text-pink-200 font-medium text-lg">— Apol Leonida</footer>
                </blockquote>
            </div>
        </div>
    </div>
</body>

</html>