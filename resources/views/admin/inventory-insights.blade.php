<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col space-y-4 md:flex-row md:justify-between md:items-center md:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory Insights') }}
            </h2>
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                <!-- Time Period Filter -->
                <form method="GET" action="{{ route('admin.dashboard.inventory') }}" id="periodFilterForm" class="w-full sm:w-auto">
                    @if($location)
                    <input type="hidden" name="location" value="{{ $location }}">
                    @endif
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Dashboard Navigation -->
            <x-dashboard-navigation current="inventory" :period="$period" />

            <!-- Validation Errors -->
            @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
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
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Location Filter -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.dashboard.inventory') }}" class="flex items-end gap-4">
                        <input type="hidden" name="period" value="{{ $period }}">
                        <div class="flex-1">
                            <label for="location" class="block text-sm font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent mb-2">
                                <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Filter by Location
                            </label>
                            <select name="location" id="location"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-purple-300 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:border-pink-500 sm:text-sm rounded-lg transition-all duration-200"
                                onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @php
                                $locations = \App\Models\Inventory::select('location')
                                ->distinct()
                                ->orderBy('location')
                                ->pluck('location');
                                @endphp
                                @foreach($locations as $loc)
                                <option value="{{ $loc }}" {{ $location === $loc ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $loc)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @if($location)
                        <a href="{{ route('admin.dashboard.inventory', ['period' => $period]) }}"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-100 to-gray-200 border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:from-gray-200 hover:to-gray-300 hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear Filter
                        </a>
                        @endif
                    </form>
                    @if($location)
                    <div class="mt-3 text-sm text-gray-600">
                        <span class="font-medium">Showing data for:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-pink-100 to-purple-100 text-purple-800">
                            {{ ucwords(str_replace('_', ' ', $location)) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            @if($analytics)
            <!-- Low Stock Alerts Section -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center mr-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent">Low Stock Alerts</h3>
                            <span class="ml-3 px-3 py-1 text-sm font-bold text-red-800 bg-gradient-to-r from-red-100 to-red-200 rounded-full">
                                {{ $analytics['inventory_alerts']['low_stock_count'] }}
                            </span>
                        </div>
                    </div>

                    @if($analytics['inventory_alerts']['items']->isEmpty())
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-600">All inventory levels are healthy. No alerts at this time.</p>
                    </div>
                    @else
                    <div class="overflow-x-auto -mx-6 sm:mx-0">
                        <div class="inline-block min-w-full align-middle">
                            <div class="overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Severity
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Location
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Current Stock
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Reorder Level
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Stock Level
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($analytics['inventory_alerts']['items'] as $alert)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($alert['severity'] === 'out_of_stock')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-600 text-white">
                                                    OUT OF STOCK
                                                </span>
                                                @elseif($alert['severity'] === 'critical')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white">
                                                    CRITICAL
                                                </span>
                                                @elseif($alert['severity'] === 'warning')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-600 text-white">
                                                    WARNING
                                                </span>
                                                @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-600 text-white">
                                                    NORMAL
                                                </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $alert['product_name'] }}</div>
                                                @if($alert['variant_name'])
                                                <div class="text-sm text-gray-500">{{ $alert['variant_name'] }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ ucwords(str_replace('_', ' ', $alert['location'])) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-bold {{ $alert['quantity_available'] <= 0 ? 'text-red-600' : 'text-gray-900' }}">
                                                    {{ $alert['quantity_available'] }} units
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $alert['reorder_level'] }} units
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="text-sm font-bold mr-2 {{ $alert['stock_percentage'] <= 25 ? 'text-red-600' : ($alert['stock_percentage'] <= 50 ? 'text-yellow-600' : 'text-gray-900') }}">
                                                        {{ $alert['stock_percentage'] }}%
                                                    </span>
                                                    <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[80px]">
                                                        <div class="h-2 rounded-full {{ $alert['stock_percentage'] <= 25 ? 'bg-red-600' : ($alert['stock_percentage'] <= 50 ? 'bg-yellow-600' : 'bg-green-600') }}"
                                                            style="width: {{ min($alert['stock_percentage'], 100) }}%"></div>
                                                    </div>
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

            <!-- Recent Inventory Movements Section -->
            @if(isset($analytics['recent_movements']))
            <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center mr-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent">Recent Inventory Movements</h3>
                        </div>
                    </div>

                    @if($analytics['recent_movements']->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-600">No recent inventory movements.</p>
                    </div>
                    @else
                    <div class="overflow-x-auto -mx-6 sm:mx-0">
                        <div class="inline-block min-w-full align-middle">
                            <div class="overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Location
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Reference
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($analytics['recent_movements'] as $movement)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $movement->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $movement->product->name ?? 'Unknown' }}</div>
                                                @if($movement->variant)
                                                <div class="text-sm text-gray-500">{{ $movement->variant->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $movement->getTypeBadgeColor() }}">
                                                    {{ $movement->movement_type_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-bold {{ $movement->getQuantityColorClass() }}">
                                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $movement->location_display }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($movement->transaction_reference)
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $movement->transaction_reference['id'] }}
                                                </span>
                                                @elseif($movement->reference_display)
                                                {{ $movement->reference_display }}
                                                @else
                                                —
                                                @endif
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
            @endif

            <!-- Revenue by Location Section -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-2xl transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mr-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-700 via-purple-700 to-indigo-700 bg-clip-text text-transparent">Revenue by Location</h3>
                        </div>
                    </div>

                    @if($analytics['revenue_by_location']->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-600">No revenue data available for the selected period.</p>
                    </div>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($analytics['revenue_by_location'] as $locationData)
                        <div class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg p-6 border border-purple-100">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $locationData->location_name }}</h4>
                                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-sm text-gray-600">Total Revenue</p>
                                    <p class="text-2xl font-bold text-purple-700">₱{{ number_format($locationData->total_revenue, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Orders</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ $locationData->order_count }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>