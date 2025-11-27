<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Staff Dashboard') }}
            </h2>
            <div class="text-sm text-gray-600">
                Welcome back, {{ Auth::user()->name }}!
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Quick Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Products -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Products</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalProducts) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm text-gray-600">
                                <span class="text-green-600 font-medium">{{ number_format($activeProducts) }}</span> active, 
                                <span class="text-gray-500">{{ number_format($inactiveProducts) }}</span> inactive
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Low Stock Alert</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($lowStockCount) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            @if($lowStockCount > 0)
                                <a href="{{ route('staff.inventory.alerts') }}" class="text-sm text-red-600 hover:text-red-800 font-medium">
                                    Items need restock →
                                </a>
                            @else
                                <span class="text-sm text-gray-600">All items well stocked</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Out of Stock -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-orange-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Out of Stock</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($outOfStockCount) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            @if($outOfStockCount > 0)
                                <a href="{{ route('staff.inventory.list') }}" class="text-sm text-orange-600 hover:text-orange-800 font-medium">
                                    Restock needed →
                                </a>
                            @else
                                <span class="text-sm text-gray-600">No stock issues</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Featured Products -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-purple-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Featured Products</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($featuredProducts) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('staff.products.visibility.manage') }}" class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                                Manage visibility →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Alert Widget -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Inventory Alerts</h3>
                        <a href="{{ route('staff.inventory.alerts') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All Alerts →
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Critical Alerts -->
                        <a href="{{ route('staff.inventory.alerts') }}" class="block p-4 bg-red-50 border-2 border-red-200 rounded-lg hover:bg-red-100 hover:border-red-300 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-red-800">Critical</p>
                                    <p class="text-3xl font-bold text-red-600 mt-1">{{ $alertDashboard['alert_counts']['critical'] }}</p>
                                </div>
                                <div class="p-3 bg-red-200 rounded-full">
                                    <svg class="h-6 w-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-red-700 mt-2">≤ 25% of reorder level</p>
                        </a>

                        <!-- Warning Alerts -->
                        <a href="{{ route('staff.inventory.alerts') }}" class="block p-4 bg-yellow-50 border-2 border-yellow-200 rounded-lg hover:bg-yellow-100 hover:border-yellow-300 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">Warning</p>
                                    <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $alertDashboard['alert_counts']['warning'] }}</p>
                                </div>
                                <div class="p-3 bg-yellow-200 rounded-full">
                                    <svg class="h-6 w-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-yellow-700 mt-2">26-50% of reorder level</p>
                        </a>

                        <!-- Out of Stock Alerts -->
                        <a href="{{ route('staff.inventory.alerts') }}" class="block p-4 bg-orange-50 border-2 border-orange-200 rounded-lg hover:bg-orange-100 hover:border-orange-300 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-orange-800">Out of Stock</p>
                                    <p class="text-3xl font-bold text-orange-600 mt-1">{{ $alertDashboard['alert_counts']['error'] }}</p>
                                </div>
                                <div class="p-3 bg-orange-200 rounded-full">
                                    <svg class="h-6 w-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-orange-700 mt-2">No stock available</p>
                        </a>
                    </div>

                    @if($alertDashboard['total_alerts'] > 0)
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold">{{ $alertDashboard['total_alerts'] }}</span> total alerts require attention
                            </p>
                        </div>
                    @else
                        <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-sm text-green-700 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                All inventory levels are healthy
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Main Action Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Walk-In Transaction -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-indigo-600 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="p-4 bg-indigo-600 rounded-xl shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-center text-lg font-bold text-gray-900 mb-2">Walk-In POS</h3>
                        <p class="text-center text-gray-600 text-sm mb-6 min-h-[40px]">Process in-store purchases and manage walk-in customer transactions</p>
                        <div class="space-y-3">
                            <a href="{{ route('staff.walk-in-transaction.create') }}" class="block w-full text-center px-4 py-3 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all duration-200">
                                <span class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    New Transaction
                                </span>
                            </a>
                            <a href="{{ route('staff.walk-in-transaction.history') }}" class="block w-full text-center px-4 py-3 bg-indigo-50 border border-indigo-200 rounded-lg font-semibold text-sm text-indigo-700 hover:bg-indigo-100 transition-all duration-200">
                                View History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product Management -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-blue-600 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="p-4 bg-blue-600 rounded-xl shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-center text-lg font-bold text-gray-900 mb-2">Product Management</h3>
                        <p class="text-center text-gray-600 text-sm mb-6 min-h-[40px]">Upload new products, update existing items, and manage product catalog</p>
                        <div class="space-y-3">
                            <a href="{{ route('staff.products.create') }}" class="block w-full text-center px-4 py-3 bg-blue-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-blue-700 shadow-md hover:shadow-lg transition-all duration-200">
                                <span class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Upload New Product
                                </span>
                            </a>
                            <a href="{{ route('staff.products.index') }}" class="block w-full text-center px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg font-semibold text-sm text-blue-700 hover:bg-blue-100 transition-all duration-200">
                                View All Products
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Management -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-orange-600 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="p-4 bg-orange-600 rounded-xl shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-center text-lg font-bold text-gray-900 mb-2">Order Management</h3>
                        <p class="text-center text-gray-600 text-sm mb-6 min-h-[40px]">Process orders, update order status, and manage fulfillment</p>
                        <div class="space-y-3">
                            <a href="#" class="block w-full text-center px-4 py-3 bg-orange-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-orange-700 shadow-md hover:shadow-lg transition-all duration-200">
                                <span class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pending Orders
                                </span>
                            </a>
                            <a href="#" class="block w-full text-center px-4 py-3 bg-orange-50 border border-orange-200 rounded-lg font-semibold text-sm text-orange-700 hover:bg-orange-100 transition-all duration-200">
                                All Orders
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Inventory Management -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-t-4 border-green-600 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="p-4 bg-green-600 rounded-xl shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-center text-lg font-bold text-gray-900 mb-2">Inventory Control</h3>
                        <p class="text-center text-gray-600 text-sm mb-6 min-h-[40px]">Update stock levels, track inventory, and manage warehouse</p>
                        <div class="space-y-3">
                            <a href="{{ route('staff.inventory.index') }}" class="block w-full text-center px-4 py-3 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 shadow-md hover:shadow-lg transition-all duration-200">
                                <span class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Update Stock
                                </span>
                            </a>
                            <a href="{{ route('staff.inventory.list') }}" class="block w-full text-center px-4 py-3 bg-green-50 border border-green-200 rounded-lg font-semibold text-sm text-green-700 hover:bg-green-100 transition-all duration-200">
                                View Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Quick Links -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Orders -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                        </div>
                        <div class="space-y-4">
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="mt-2">No recent orders</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Low Stock Alerts</h3>
                            <a href="{{ route('staff.inventory.alerts') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                        </div>
                        <div class="space-y-4">
                            @if($lowStockItems->count() > 0)
                                @foreach($lowStockItems as $item)
                                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $item->product->name }}
                                                @if($item->variant)
                                                    <span class="text-gray-600">({{ $item->variant->name }})</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-600 mt-1">
                                                SKU: {{ $item->product->sku }}
                                                @if($item->variant)
                                                    - {{ $item->variant->sku }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right ml-4">
                                            <p class="text-sm font-semibold text-red-600">{{ $item->quantity_available }} left</p>
                                            <p class="text-xs text-gray-500">Reorder: {{ $item->reorder_level }}</p>
                                        </div>
                                    </div>
                                @endforeach
                                @if($lowStockCount > 5)
                                    <div class="text-center pt-2">
                                        <a href="{{ route('staff.inventory.alerts') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            View {{ $lowStockCount - 5 }} more items →
                                        </a>
                                    </div>
                                @endif
                            @elseif($outOfStockItems->count() > 0)
                                <div class="mb-4">
                                    <p class="text-sm font-medium text-gray-700 mb-3">Out of Stock Items:</p>
                                    @foreach($outOfStockItems->take(3) as $item)
                                        <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-200 mb-2">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $item->product->name }}
                                                    @if($item->variant)
                                                        <span class="text-gray-600">({{ $item->variant->name }})</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="text-xs font-semibold text-orange-600 px-2 py-1 bg-orange-100 rounded">OUT OF STOCK</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="mt-2">All items are well stocked</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Quick Actions -->
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <a href="{{ route('staff.walk-in-transaction.index') }}" class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition border-2 border-indigo-200">
                            <svg class="h-8 w-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-indigo-700">Walk-In POS</span>
                        </a>
                        <a href="{{ route('staff.categories.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Categories</span>
                            <span class="text-xs text-gray-500 mt-1">{{ $totalCategories }}</span>
                        </a>
                        <a href="{{ route('staff.brands.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Brands</span>
                            <span class="text-xs text-gray-500 mt-1">{{ $totalBrands }}</span>
                        </a>
                        <a href="{{ route('staff.promotions.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-pink-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Promotions</span>
                        </a>
                        <a href="{{ route('staff.pricing.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Pricing</span>
                        </a>
                        <a href="{{ route('staff.inventory.movements') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Movements</span>
                        </a>
                        <a href="{{ route('staff.inventory.bulk-update.form') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-teal-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Bulk Update</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Inventory Movements -->
            @if($recentMovements->count() > 0)
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Recent Inventory Movements</h3>
                            <p class="text-sm text-gray-500 mt-1">Last 7 days • {{ $recentMovements->count() }} {{ Str::plural('movement', $recentMovements->count()) }}</p>
                        </div>
                        <a href="{{ route('staff.inventory.movements') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Product</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Quantity</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentMovements->take(10) as $movement)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $movement->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $movement->product->name }}
                                        @if($movement->variant)
                                            <span class="text-gray-600">({{ $movement->variant->name }})</span>
                                        @endif
                                        <div class="text-xs text-gray-500">{{ $movement->product->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $movement->getTypeBadgeColor() }} 
                                            @if($movement->movement_type === 'purchase') text-blue-800
                                            @elseif($movement->movement_type === 'sale') text-green-800
                                            @elseif($movement->movement_type === 'adjustment') text-purple-800
                                            @elseif($movement->movement_type === 'return') text-yellow-800
                                            @elseif($movement->movement_type === 'damage') text-red-800
                                            @elseif($movement->movement_type === 'transfer') text-indigo-800
                                            @else text-gray-800
                                            @endif">
                                            {{ ucfirst($movement->movement_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $movement->getQuantityColorClass() }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 break-words">
                                        <div class="max-w-md">
                                            <x-movement-notes :movement="$movement" />
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>