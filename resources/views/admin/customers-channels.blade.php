<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col space-y-4 md:flex-row md:justify-between md:items-center md:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customers & Channels') }}
            </h2>
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                <!-- Time Period Filter -->
                <form method="GET" action="{{ route('admin.dashboard.customers') }}" id="periodFilterForm" class="w-full sm:w-auto">
                    <select name="period" id="periodFilter"
                        class="w-full sm:w-auto rounded-lg border-purple-300 shadow-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 transition-all duration-200"
                        onchange="document.getElementById('periodFilterForm').submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Dashboard Navigation -->
            <x-dashboard-navigation current="customers" :period="$period" />

            <!-- Validation Errors -->
            @if($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($error))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($analytics))
            <!-- Customer Metrics Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">Customer Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-analytics-card
                        title="Total Customers"
                        :value="number_format($analytics['customer_metrics']['total_customers'] ?? 0)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />'
                        color="pink" />

                    <x-analytics-card
                        title="New Customers"
                        :value="number_format($analytics['customer_metrics']['new_customers'] ?? 0)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />'
                        color="purple"
                        :change="$analytics['customer_metrics']['growth_rate'] ?? 0"
                        :changeType="($analytics['customer_metrics']['growth_rate'] ?? 0) >= 0 ? 'increase' : 'decrease'" />

                    <x-analytics-card
                        title="Customer Growth Rate"
                        :value="number_format($analytics['customer_metrics']['growth_rate'] ?? 0, 1) . '%'"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />'
                        color="indigo"
                        :change="$analytics['customer_metrics']['growth_rate'] ?? 0"
                        :changeType="($analytics['customer_metrics']['growth_rate'] ?? 0) >= 0 ? 'increase' : 'decrease'" />
                </div>
            </div>

            <!-- Channel Comparison Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">Sales Channel Comparison</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Walk-in Channel -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-pink-500 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold bg-gradient-to-r from-pink-600 to-pink-700 bg-clip-text text-transparent">Walk-in Sales</h4>
                                <div class="w-12 h-12 bg-gradient-to-br from-pink-400 to-pink-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Revenue</p>
                                    <p class="text-2xl font-bold bg-gradient-to-r from-pink-600 to-pink-700 bg-clip-text text-transparent">₱{{ number_format($analytics['channel_comparison']['walk_in']['revenue'] ?? 0, 2) }}</p>
                                    <p class="text-sm text-pink-600 font-medium">{{ number_format($analytics['channel_comparison']['walk_in']['revenue_percentage'] ?? 0, 1) }}% of total</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Orders</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ number_format($analytics['channel_comparison']['walk_in']['order_count'] ?? 0) }}</p>
                                    <p class="text-sm text-pink-600 font-medium">{{ number_format($analytics['channel_comparison']['walk_in']['order_percentage'] ?? 0, 1) }}% of total</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Online Channel -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-indigo-500 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">Online Sales</h4>
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Revenue</p>
                                    <p class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">₱{{ number_format($analytics['channel_comparison']['online']['revenue'] ?? 0, 2) }}</p>
                                    <p class="text-sm text-indigo-600 font-medium">{{ number_format($analytics['channel_comparison']['online']['revenue_percentage'] ?? 0, 1) }}% of total</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Orders</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ number_format($analytics['channel_comparison']['online']['order_count'] ?? 0) }}</p>
                                    <p class="text-sm text-indigo-600 font-medium">{{ number_format($analytics['channel_comparison']['online']['order_percentage'] ?? 0, 1) }}% of total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Method Distribution Section -->
            <div class="mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6">Payment Method Distribution</h3>
                        <x-payment-methods-chart :paymentData="$analytics['payment_distribution']" />
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>