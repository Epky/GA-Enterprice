<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory Movements') }}
            </h2>
            <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Movements</h3>
                    <form method="GET" action="{{ route('staff.inventory.movements') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <!-- Movement Type -->
                            <div>
                                <label for="movement_type" class="block text-sm font-medium text-gray-700 mb-1">Movement Type</label>
                                <select id="movement_type" name="movement_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Types</option>
                                    <optgroup label="Business Movements">
                                        <option value="purchase" {{ request('movement_type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                                        <option value="sale" {{ request('movement_type') === 'sale' ? 'selected' : '' }}>Sale</option>
                                        <option value="return" {{ request('movement_type') === 'return' ? 'selected' : '' }}>Return</option>
                                        <option value="damage" {{ request('movement_type') === 'damage' ? 'selected' : '' }}>Damage</option>
                                        <option value="adjustment" {{ request('movement_type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                                        <option value="transfer" {{ request('movement_type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                                    </optgroup>
                                    <optgroup label="System Movements">
                                        <option value="reservation" {{ request('movement_type') === 'reservation' ? 'selected' : '' }}>Reservation</option>
                                        <option value="release" {{ request('movement_type') === 'release' ? 'selected' : '' }}>Release</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select id="location" name="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Locations</option>
                                    <option value="main_warehouse" {{ request('location') === 'main_warehouse' ? 'selected' : '' }}>Main Warehouse</option>
                                    <option value="store_front" {{ request('location') === 'store_front' ? 'selected' : '' }}>Store Front</option>
                                    <option value="online_fulfillment" {{ request('location') === 'online_fulfillment' ? 'selected' : '' }}>Online Fulfillment</option>
                                    <option value="returns_area" {{ request('location') === 'returns_area' ? 'selected' : '' }}>Returns Area</option>
                                </select>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            <!-- Submit -->
                            <div class="flex items-end">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    Filter
                                </button>
                            </div>
                        </div>

                        <!-- Additional Options -->
                        <div class="flex items-center space-x-6 pt-2 border-t border-gray-200">
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="include_system_movements" 
                                    name="include_system_movements" 
                                    value="1"
                                    {{ $includeSystemMovements ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    onchange="this.form.submit()"
                                >
                                <label for="include_system_movements" class="ml-2 block text-sm text-gray-700">
                                    Show System Movements
                                    <span class="text-xs text-gray-500">(reservations & releases)</span>
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="group_related" 
                                    name="group_related" 
                                    value="1"
                                    {{ $groupRelated ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    onchange="this.form.submit()"
                                >
                                <label for="group_related" class="ml-2 block text-sm text-gray-700">
                                    Group Related Movements
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Movements Indicator -->
            @if($includeSystemMovements)
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <span class="font-medium">System movements are visible.</span>
                            This includes internal operations like reservations and releases. Uncheck "Show System Movements" to hide them.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Movements Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Movement History</h3>
                        <div class="text-sm text-gray-500">
                            Total: {{ $movements->total() }} movements
                        </div>
                    </div>

                    <div class="movements-table-container max-h-[600px] overflow-y-auto">
                        <table class="movements-table min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Date & Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Product</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Quantity</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50 mobile-hide">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50 mobile-hide">Performed By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Reason/Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($movements as $item)
                                    @php
                                        // Handle grouped vs ungrouped data
                                        $movement = is_array($item) ? $item['primary'] : $item;
                                        $relatedMovements = is_array($item) ? $item['related'] : collect();
                                        $transactionRef = is_array($item) ? $item['transaction_ref'] : null;
                                    @endphp
                                    
                                    <tr class="{{ $groupRelated && $relatedMovements->isNotEmpty() ? 'movement-primary' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>{{ $movement->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $movement->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $movement->display_name }}</div>
                                            @if($movement->product)
                                            <div class="text-xs text-gray-500">SKU: {{ $movement->product->sku }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $movement->getTypeBadgeColor() }} 
                                                @if($movement->movement_type === 'purchase') text-blue-800
                                                @elseif($movement->movement_type === 'sale') text-green-800
                                                @elseif($movement->movement_type === 'return') text-yellow-800
                                                @elseif($movement->movement_type === 'damage') text-red-800
                                                @elseif($movement->movement_type === 'adjustment') text-purple-800
                                                @elseif($movement->movement_type === 'transfer') text-indigo-800
                                                @elseif($movement->movement_type === 'reservation') text-orange-800
                                                @elseif($movement->movement_type === 'release') text-gray-800
                                                @else text-gray-800
                                                @endif">
                                                {{ $movement->movement_type_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium {{ $movement->getQuantityColorClass() }}">
                                                @if($movement->quantity > 0)+@endif{{ $movement->quantity }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 mobile-hide">
                                            {{ $movement->location_display }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap mobile-hide">
                                            <div class="text-sm text-gray-900">{{ $movement->performedBy?->name ?? 'System' }}</div>
                                            <div class="text-xs text-gray-500">{{ $movement->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-movement-notes :movement="$movement" />
                                        </td>
                                    </tr>
                                    
                                    {{-- Display related movements if grouped --}}
                                    @if($groupRelated && $relatedMovements->isNotEmpty())
                                        @foreach($relatedMovements as $related)
                                        <tr class="movement-related {{ $loop->last ? 'movement-group-end' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 pl-12">
                                                <div class="flex items-center">
                                                    <svg class="h-4 w-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <div>
                                                        <div>{{ $related->created_at->format('M d, Y') }}</div>
                                                        <div class="text-xs text-gray-400">{{ $related->created_at->format('h:i A') }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <div class="text-xs">{{ $related->display_name }}</div>
                                                @if($related->product)
                                                <div class="text-xs text-gray-400">SKU: {{ $related->product->sku }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $related->getTypeBadgeColor() }}
                                                    @if($related->movement_type === 'reservation') text-orange-800
                                                    @elseif($related->movement_type === 'release') text-gray-800
                                                    @else text-gray-800
                                                    @endif">
                                                    {{ $related->movement_type_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-xs {{ $related->getQuantityColorClass() }}">
                                                    @if($related->quantity > 0)+@endif{{ $related->quantity }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400 mobile-hide">
                                                {{ $related->location_display }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400 mobile-hide">
                                                {{ $related->performedBy?->name ?? 'System' }}
                                            </td>
                                            <td class="px-6 py-4 text-xs">
                                                <x-movement-notes :movement="$related" :compact="true" />
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No inventory movements found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($movements->hasPages())
                    <div class="mt-6">
                        {{ $movements->links() }}
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
