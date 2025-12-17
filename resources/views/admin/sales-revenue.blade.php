<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col space-y-4 md:flex-row md:justify-between md:items-center md:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Sales & Revenue') }}
            </h2>
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                <!-- Time Period Filter -->
                <form method="GET" action="{{ route('admin.dashboard.sales') }}" id="periodFilterForm" class="w-full sm:w-auto">
                    <select name="period" id="periodFilter"
                        class="w-full sm:w-auto rounded-lg border-purple-300 shadow-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 transition-all duration-200"
                        onchange="document.getElementById('periodFilterForm').submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </form>

                <!-- Export Button -->
                <form method="GET" action="{{ route('admin.analytics.export') }}" class="w-full sm:w-auto">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:from-pink-600 hover:via-purple-600 hover:to-indigo-600 hover:shadow-xl hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export CSV
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Dashboard Navigation -->
            <x-dashboard-navigation current="sales" :period="$period" />

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
            <!-- Revenue Metrics Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">Revenue Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-analytics-card
                        title="Total Revenue"
                        :value="'₱' . number_format($analytics['revenue']['total'] ?? 0, 2)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
                        color="pink"
                        :change="$analytics['revenue']['change_percent'] ?? 0"
                        :changeType="($analytics['revenue']['change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'" />

                    <x-analytics-card
                        title="Gross Profit"
                        :value="'₱' . number_format($analytics['profit_metrics']['gross_profit'] ?? 0, 2)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />'
                        color="purple"
                        :change="$analytics['profit_metrics']['profit_change_percent'] ?? 0"
                        :changeType="($analytics['profit_metrics']['profit_change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'" />

                    <x-analytics-card
                        title="Profit Margin"
                        :value="number_format($analytics['profit_metrics']['profit_margin'] ?? 0, 1) . '%'"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />'
                        color="indigo"
                        :change="null"
                        changeType="margin" />
                </div>
            </div>

            <!-- Order Statistics Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">Order Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-analytics-card
                        title="Total Orders"
                        :value="number_format($analytics['order_metrics']['total_orders'] ?? 0)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />'
                        color="pink"
                        :change="$analytics['order_metrics']['change_percent'] ?? 0"
                        :changeType="($analytics['order_metrics']['change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'" />

                    <x-analytics-card
                        title="Average Order Value"
                        :value="'₱' . number_format($analytics['order_metrics']['avg_order_value'] ?? 0, 2)"
                        icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />'
                        color="purple"
                        :change="$analytics['order_metrics']['aov_change_percent'] ?? 0"
                        :changeType="($analytics['order_metrics']['aov_change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'" />

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-indigo-500 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="p-6">
                            <p class="text-sm font-medium text-gray-600">Order Type Breakdown</p>
                            <div class="mt-4 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Walk-in</span>
                                    <span class="text-lg font-bold bg-gradient-to-r from-pink-600 to-pink-700 bg-clip-text text-transparent">
                                        {{ number_format($analytics['order_metrics']['walk_in_orders'] ?? 0) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Online</span>
                                    <span class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">
                                        {{ number_format($analytics['order_metrics']['online_orders'] ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Trend Chart Section -->
            <div class="mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6">Sales Trend</h3>
                        <x-sales-chart :chartData="$analytics['sales_trend']" :period="$period" />
                    </div>
                </div>
            </div>

            <!-- Top Products Section -->
            <div class="mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6">Top Selling Products</h3>
                        <x-top-products-table :products="$analytics['top_products']" />
                    </div>
                </div>
            </div>

            <!-- Category & Brand Breakdown Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6">Revenue by Category</h3>
                        <x-category-breakdown :categories="$analytics['category_breakdown']" title="Revenue by Category" />
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6">Revenue by Brand</h3>
                        <x-category-breakdown :categories="$analytics['brand_breakdown']" title="Revenue by Brand" />
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>