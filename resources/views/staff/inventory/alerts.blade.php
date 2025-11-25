<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory Alerts') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Location Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('staff.inventory.alerts') }}" class="flex items-end gap-4">
                        <div class="flex-1">
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Filter by Location
                            </label>
                            <select name="location" id="location" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @php
                                    $locations = \App\Models\Inventory::select('location')
                                        ->distinct()
                                        ->orderBy('location')
                                        ->pluck('location');
                                @endphp
                                @foreach($locations as $loc)
                                    <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $loc)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply Filter
                            </button>
                            @if(request('location'))
                                <a href="{{ route('staff.inventory.alerts') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>
                    @if(request('location'))
                        <div class="mt-3 text-sm text-gray-600">
                            <span class="font-medium">Showing alerts for:</span> 
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ ucwords(str_replace('_', ' ', request('location'))) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Alert Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Critical Stock -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Critical Stock</p>
                                <p class="text-3xl font-bold text-red-700">{{ $alerts['summary']['critical_count'] }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-gray-600">≤ 25% of reorder level</span>
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
                                <p class="text-3xl font-bold text-orange-700">{{ $alerts['summary']['out_of_stock_count'] }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-gray-600">0 units available</span>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Warning -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-yellow-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Low Stock</p>
                                <p class="text-3xl font-bold text-yellow-700">{{ $alerts['summary']['low_stock_count'] }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-xs text-gray-600">26-50% of reorder level</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($alerts['summary']['total_alerts'] === 0)
            <!-- No Alerts Message -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">All Inventory Levels Are Healthy</h3>
                    <p class="text-sm text-gray-600">No alerts at this time. All stock levels are above their reorder thresholds.</p>
                </div>
            </div>
            @else

            <!-- Critical Alerts Section -->
            @if($alerts['alerts']['critical_stock']->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <svg class="h-6 w-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-red-900">Critical Stock Levels</h3>
                        <span class="ml-3 px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full">
                            {{ $alerts['alerts']['critical_stock']->count() }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($alerts['alerts']['critical_stock'] as $alert)
                        @php
                            $inventory = $alert['inventory'];
                            $stockPercentage = $inventory->reorder_level > 0 
                                ? round(($inventory->quantity_available / $inventory->reorder_level) * 100, 1) 
                                : 0;
                        @endphp
                        <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded-r-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white mr-3">
                                            CRITICAL
                                        </span>
                                        <h4 class="text-base font-semibold text-gray-900">{{ $inventory->display_name }}</h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                                        <div>
                                            <p class="text-xs text-gray-600">Current Stock</p>
                                            <p class="text-lg font-bold text-red-700">{{ $inventory->quantity_available }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Reorder Level</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ $inventory->reorder_level }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Location</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $inventory->location }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Stock Level</p>
                                            <div class="flex items-center">
                                                <p class="text-lg font-bold text-red-700">{{ $stockPercentage }}%</p>
                                                <div class="ml-2 flex-1 bg-gray-200 rounded-full h-2 max-w-[60px]">
                                                    <div class="bg-red-600 h-2 rounded-full" style="width: {{ min($stockPercentage, 100) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-white rounded border border-red-200">
                                        <p class="text-sm text-gray-700">
                                            <span class="font-semibold text-red-700">⚠ Immediate Action Required:</span> 
                                            {{ $alert['suggested_action'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="{{ route('staff.products.show', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Product
                                    </a>
                                    <a href="{{ route('staff.inventory.edit', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Reorder Now
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Out of Stock Alerts Section -->
            @if($alerts['alerts']['out_of_stock']->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <svg class="h-6 w-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-orange-900">Out of Stock</h3>
                        <span class="ml-3 px-3 py-1 text-sm font-semibold text-orange-800 bg-orange-100 rounded-full">
                            {{ $alerts['alerts']['out_of_stock']->count() }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($alerts['alerts']['out_of_stock'] as $alert)
                        @php
                            $inventory = $alert['inventory'];
                            $stockPercentage = 0;
                        @endphp
                        <div class="border-l-4 border-orange-500 bg-orange-50 p-4 rounded-r-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-600 text-white mr-3">
                                            OUT OF STOCK
                                        </span>
                                        <h4 class="text-base font-semibold text-gray-900">{{ $inventory->display_name }}</h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                                        <div>
                                            <p class="text-xs text-gray-600">Current Stock</p>
                                            <p class="text-lg font-bold text-orange-700">{{ $inventory->quantity_available }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Reorder Level</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ $inventory->reorder_level }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Location</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $inventory->location }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Stock Level</p>
                                            <div class="flex items-center">
                                                <p class="text-lg font-bold text-orange-700">{{ $stockPercentage }}%</p>
                                                <div class="ml-2 flex-1 bg-gray-200 rounded-full h-2 max-w-[60px]">
                                                    <div class="bg-orange-600 h-2 rounded-full" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-white rounded border border-orange-200">
                                        <p class="text-sm text-gray-700">
                                            <span class="font-semibold text-orange-700">⚠ Action Required:</span> 
                                            {{ $alert['suggested_action'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="{{ route('staff.products.show', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Product
                                    </a>
                                    <a href="{{ route('staff.inventory.edit', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Reorder Now
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Low Stock Warning Alerts Section -->
            @if($alerts['alerts']['low_stock']->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <svg class="h-6 w-6 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-yellow-900">Low Stock Warnings</h3>
                        <span class="ml-3 px-3 py-1 text-sm font-semibold text-yellow-800 bg-yellow-100 rounded-full">
                            {{ $alerts['alerts']['low_stock']->count() }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($alerts['alerts']['low_stock'] as $alert)
                        @php
                            $inventory = $alert['inventory'];
                            $stockPercentage = $inventory->reorder_level > 0 
                                ? round(($inventory->quantity_available / $inventory->reorder_level) * 100, 1) 
                                : 0;
                        @endphp
                        <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4 rounded-r-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-600 text-white mr-3">
                                            LOW STOCK
                                        </span>
                                        <h4 class="text-base font-semibold text-gray-900">{{ $inventory->display_name }}</h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                                        <div>
                                            <p class="text-xs text-gray-600">Current Stock</p>
                                            <p class="text-lg font-bold text-yellow-700">{{ $inventory->quantity_available }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Reorder Level</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ $inventory->reorder_level }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Location</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $inventory->location }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-600">Stock Level</p>
                                            <div class="flex items-center">
                                                <p class="text-lg font-bold text-yellow-700">{{ $stockPercentage }}%</p>
                                                <div class="ml-2 flex-1 bg-gray-200 rounded-full h-2 max-w-[60px]">
                                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ min($stockPercentage, 100) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-white rounded border border-yellow-200">
                                        <p class="text-sm text-gray-700">
                                            <span class="font-semibold text-yellow-700">ℹ Suggestion:</span> 
                                            {{ $alert['suggested_action'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="{{ route('staff.products.show', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Product
                                    </a>
                                    <a href="{{ route('staff.inventory.edit', $inventory->product_id) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-yellow-600 hover:bg-yellow-700">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Reorder Now
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @endif

        </div>
    </div>
</x-app-layout>
