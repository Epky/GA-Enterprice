<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Admin Dashboard') }}
            </h2>
            <div class="flex items-center space-x-4">
                <!-- Time Period Filter -->
                <form method="GET" action="{{ route('admin.dashboard') }}" id="periodFilterForm">
                    <select name="period" id="periodFilter" 
                            class="rounded-lg border-purple-300 shadow-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 transition-all duration-200"
                            onchange="document.getElementById('periodFilterForm').submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </form>
                
                <!-- Export Button -->
                <form method="GET" action="{{ route('admin.analytics.export') }}" id="exportForm">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:from-pink-600 hover:via-purple-600 hover:to-indigo-600 hover:shadow-xl hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 active:from-pink-700 active:via-purple-700 active:to-indigo-700 transition-all duration-200"
                            id="exportButton">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span id="exportButtonText">Export CSV</span>
                    </button>
                </form>
                
                <div class="text-sm text-gray-600">
                    Welcome back, {{ Auth::user()->name }}!
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(isset($error))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
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
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue & Orders</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <x-analytics-card 
                        title="Total Revenue"
                        :value="'₱' . number_format($analytics['revenue']['total'] ?? 0, 2)"
                        icon="currency-dollar"
                        color="pink"
                        :change="$analytics['revenue']['change_percent'] ?? 0"
                        :changeType="($analytics['revenue']['change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'"
                    />
                    
                    <x-analytics-card 
                        title="Total Orders"
                        :value="number_format($analytics['order_metrics']['total_orders'] ?? 0)"
                        icon="shopping-cart"
                        color="purple"
                        :change="$analytics['order_metrics']['change_percent'] ?? 0"
                        :changeType="($analytics['order_metrics']['change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'"
                    />
                    
                    <x-analytics-card 
                        title="Average Order Value"
                        :value="'₱' . number_format($analytics['order_metrics']['avg_order_value'] ?? 0, 2)"
                        icon="chart-bar"
                        color="indigo"
                        :change="$analytics['order_metrics']['aov_change_percent'] ?? 0"
                        :changeType="($analytics['order_metrics']['aov_change_percent'] ?? 0) >= 0 ? 'increase' : 'decrease'"
                    />
                    
                    <x-analytics-card 
                        title="Gross Profit"
                        :value="'₱' . number_format($analytics['profit_metrics']['gross_profit'] ?? 0, 2)"
                        icon="trending-up"
                        color="pink"
                        :change="$analytics['profit_metrics']['profit_margin'] ?? 0"
                        changeType="margin"
                    />
                </div>
            </div>
            @endif
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-l-4 border-pink-500 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_users']) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <span class="bg-gradient-to-r from-pink-600 to-pink-700 bg-clip-text text-transparent font-medium">+{{ $stats['new_users_today'] }}</span>
                                <span class="ml-1">today</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-l-4 border-purple-500 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Active Users</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['active_users']) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <span class="bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent font-medium">{{ number_format(($stats['active_users'] / max($stats['total_users'], 1)) * 100, 1) }}%</span>
                                <span class="ml-1">active rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New This Week -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-l-4 border-indigo-500 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">New This Week</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['new_users_this_week']) }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <span class="bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent font-medium">{{ $stats['new_users_this_month'] }}</span>
                                <span class="ml-1">this month</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border-l-4 border-{{ $systemHealth['database_status'] === 'Connected' ? 'pink' : 'red' }}-500 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-{{ $systemHealth['database_status'] === 'Connected' ? 'pink' : 'red' }}-400 to-{{ $systemHealth['database_status'] === 'Connected' ? 'pink' : 'red' }}-600 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">System Status</p>
                                <p class="text-lg font-semibold bg-gradient-to-r from-{{ $systemHealth['database_status'] === 'Connected' ? 'pink' : 'red' }}-600 to-{{ $systemHealth['database_status'] === 'Connected' ? 'pink' : 'red' }}-700 bg-clip-text text-transparent">{{ $systemHealth['database_status'] }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <span class="text-gray-600 font-medium">{{ $systemHealth['total_tables'] }}</span>
                                <span class="ml-1">tables</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($analytics))
            <!-- Channel Comparison & Customer Metrics -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Channels & Customers</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <x-analytics-card 
                        title="Walk-in Sales"
                        :value="'₱' . number_format($analytics['channel_comparison']['walk_in']['revenue'] ?? 0, 2)"
                        icon="store"
                        color="purple"
                        :change="$analytics['channel_comparison']['walk_in']['percentage'] ?? 0"
                        changeType="percentage"
                    />
                    
                    <x-analytics-card 
                        title="Online Sales"
                        :value="'₱' . number_format($analytics['channel_comparison']['online']['revenue'] ?? 0, 2)"
                        icon="globe"
                        color="indigo"
                        :change="$analytics['channel_comparison']['online']['percentage'] ?? 0"
                        changeType="percentage"
                    />
                    
                    <x-analytics-card 
                        title="Total Customers"
                        :value="number_format($analytics['customer_metrics']['total_customers'] ?? 0)"
                        icon="users"
                        color="pink"
                        :change="$analytics['customer_metrics']['growth_rate'] ?? 0"
                        :changeType="($analytics['customer_metrics']['growth_rate'] ?? 0) >= 0 ? 'increase' : 'decrease'"
                    />
                    
                    <x-analytics-card 
                        title="Low Stock Items"
                        :value="number_format($analytics['inventory_alerts']['low_stock_count'] ?? 0)"
                        icon="exclamation"
                        color="purple"
                        :change="null"
                        changeType="alert"
                    />
                </div>
            </div>
            @endif
            
            <!-- User Role Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-pink-50 via-purple-50 to-indigo-50 overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">User Role Distribution</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-white/60 backdrop-blur-sm rounded-lg hover:bg-white/80 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-gradient-to-br from-pink-400 to-pink-600 rounded-full mr-3 shadow-sm"></div>
                                    <span class="text-sm font-medium text-gray-700">Administrators</span>
                                </div>
                                <span class="text-sm font-bold bg-gradient-to-r from-pink-600 to-pink-700 bg-clip-text text-transparent">{{ $stats['admin_users'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/60 backdrop-blur-sm rounded-lg hover:bg-white/80 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full mr-3 shadow-sm"></div>
                                    <span class="text-sm font-medium text-gray-700">Staff Members</span>
                                </div>
                                <span class="text-sm font-bold bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent">{{ $stats['staff_users'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/60 backdrop-blur-sm rounded-lg hover:bg-white/80 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full mr-3 shadow-sm"></div>
                                    <span class="text-sm font-medium text-gray-700">Customers</span>
                                </div>
                                <span class="text-sm font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">{{ $stats['customer_users'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.users.create') }}" class="flex items-center p-3 bg-gradient-to-r from-pink-50 to-pink-100 hover:from-pink-100 hover:to-pink-200 hover:scale-105 hover:shadow-md rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center mr-3 shadow-sm">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold bg-gradient-to-r from-pink-700 to-pink-800 bg-clip-text text-transparent">Add New User</span>
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="flex items-center p-3 bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 hover:scale-105 hover:shadow-md rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center mr-3 shadow-sm">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold bg-gradient-to-r from-purple-700 to-purple-800 bg-clip-text text-transparent">Manage Users</span>
                            </a>
                            <a href="{{ route('admin.staff.index') }}" class="flex items-center p-3 bg-gradient-to-r from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 hover:scale-105 hover:shadow-md rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center mr-3 shadow-sm">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold bg-gradient-to-r from-indigo-700 to-indigo-800 bg-clip-text text-transparent">Manage Staff</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-all duration-300">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-4">System Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Laravel Version</span>
                                <span class="text-sm font-medium text-gray-900">{{ $systemHealth['laravel_version'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">PHP Version</span>
                                <span class="text-sm font-medium text-gray-900">{{ $systemHealth['php_version'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Database</span>
                                <span class="text-sm font-medium text-{{ $systemHealth['database_status'] === 'Connected' ? 'green' : 'red' }}-600">{{ $systemHealth['database_status'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Tables</span>
                                <span class="text-sm font-medium text-gray-900">{{ $systemHealth['total_tables'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($analytics))
            <!-- Sales Trend Chart -->
            <div class="mb-8">
                <x-sales-chart 
                    :chartData="$analytics['sales_trend']"
                    chartType="line"
                    title="Sales Trend"
                />
            </div>
            
            <!-- Top Products and Category Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Products -->
                <div>
                    <x-top-products-table 
                        :products="$analytics['top_products']"
                        :period="$period"
                    />
                </div>
                
                <!-- Category Breakdown -->
                <div>
                    <x-category-breakdown 
                        :categories="$analytics['sales_by_category']"
                        title="Sales by Category"
                    />
                </div>
            </div>
            
            <!-- Brand Breakdown and Payment Methods -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Brand Breakdown -->
                <div>
                    <x-category-breakdown 
                        :categories="$analytics['sales_by_brand']"
                        title="Sales by Brand"
                    />
                </div>
                
                <!-- Payment Methods -->
                <div>
                    <x-payment-methods-chart 
                        :paymentData="$analytics['payment_distribution']"
                    />
                </div>
            </div>
            @endif
            
            <!-- Recent Users -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-all duration-300">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent">Recent Users</h3>
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-pink-100 to-purple-100 border border-transparent rounded-lg font-semibold text-xs text-purple-700 uppercase tracking-widest hover:from-pink-200 hover:to-purple-200 hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 transition-all duration-200">View All</a>
                    </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="min-w-full divide-y divide-purple-200">
                            <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">Joined</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-purple-100">
                                @forelse($recentUsers as $user)
                                <tr class="hover:bg-gradient-to-r hover:from-pink-50 hover:to-purple-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-sm">
                                                    <span class="text-sm font-medium text-white">{{ substr($user->name, 0, 1) }}</span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($user->role === 'admin') bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800
                                            @elseif($user->role === 'staff') bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800
                                            @else bg-gradient-to-r from-indigo-100 to-indigo-200 text-indigo-800 @endif">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800' : 'bg-gradient-to-r from-purple-100 to-purple-200 text-purple-600' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No users found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Export button loading state
        document.getElementById('exportForm').addEventListener('submit', function() {
            const button = document.getElementById('exportButton');
            const buttonText = document.getElementById('exportButtonText');
            
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            buttonText.textContent = 'Exporting...';
            
            // Re-enable after 3 seconds (file should be downloaded by then)
            setTimeout(function() {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                buttonText.textContent = 'Export CSV';
            }, 3000);
        });
    </script>
    @endpush
</x-admin-layout>
