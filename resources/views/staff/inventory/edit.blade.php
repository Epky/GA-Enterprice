<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Update Stock') }} - {{ $product->name }}
            </h2>
            <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Inventory
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Stock Update Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Update Stock Levels</h3>

                            <form action="{{ route('staff.inventory.update', $product) }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <!-- Product Variant Selection (if applicable) -->
                                @if($product->variants->count() > 0)
                                <div class="mb-6">
                                    <label for="variant_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Product Variant
                                    </label>
                                    <select id="variant_id" name="variant_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Base Product</option>
                                        @foreach($product->variants as $variant)
                                        <option value="{{ $variant->id }}">{{ $variant->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('variant_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                                <!-- Movement Type -->
                                <div class="mb-6">
                                    <label for="movement_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Movement Type <span class="text-red-500">*</span>
                                    </label>
                                    <select id="movement_type" name="movement_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select movement type</option>
                                        <option value="purchase">Purchase/Restock (Add Stock)</option>
                                        <option value="sale">Sale (Remove Stock)</option>
                                        <option value="return">Return (Add Stock)</option>
                                        <option value="damage">Damage/Loss (Remove Stock)</option>
                                        <option value="adjustment">Adjustment (Add/Remove)</option>
                                    </select>
                                    @error('movement_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Quantity -->
                                <div class="mb-6">
                                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="quantity" name="quantity" required min="1" value="{{ old('quantity') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter quantity">
                                    <p class="mt-1 text-xs text-gray-500">For adjustments, use positive numbers to add stock or negative numbers to remove stock</p>
                                    @error('quantity')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Location -->
                                <div class="mb-6">
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                        Location <span class="text-red-500">*</span>
                                    </label>
                                    <select id="location" name="location" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="main_warehouse">Main Warehouse</option>
                                        <option value="store_front">Store Front</option>
                                        <option value="online_fulfillment">Online Fulfillment</option>
                                        <option value="returns_area">Returns Area</option>
                                    </select>
                                    @error('location')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Reason -->
                                <div class="mb-6">
                                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                                        Reason Code
                                    </label>
                                    <select id="reason_select" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-2">
                                        <option value="">Select a reason...</option>
                                        <optgroup label="Inbound Reasons">
                                            <option value="New shipment received">New shipment received</option>
                                            <option value="Supplier delivery">Supplier delivery</option>
                                            <option value="Transfer from another location">Transfer from another location</option>
                                            <option value="Customer return">Customer return</option>
                                            <option value="Production completed">Production completed</option>
                                        </optgroup>
                                        <optgroup label="Outbound Reasons">
                                            <option value="Customer sale">Customer sale</option>
                                            <option value="Transfer to another location">Transfer to another location</option>
                                            <option value="Damaged goods">Damaged goods</option>
                                            <option value="Expired products">Expired products</option>
                                            <option value="Theft or loss">Theft or loss</option>
                                            <option value="Sample or promotional use">Sample or promotional use</option>
                                        </optgroup>
                                        <optgroup label="Adjustment Reasons">
                                            <option value="Inventory count correction">Inventory count correction</option>
                                            <option value="System error correction">System error correction</option>
                                            <option value="Physical inventory audit">Physical inventory audit</option>
                                            <option value="Reconciliation">Reconciliation</option>
                                        </optgroup>
                                        <option value="custom">Other (specify below)</option>
                                    </select>
                                    <input type="text" id="reason" name="reason" value="{{ old('reason') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter custom reason or select from dropdown above">
                                    <p class="mt-1 text-xs text-gray-500">Select a predefined reason or enter a custom one</p>
                                    @error('reason')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-6">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                        Additional Notes
                                    </label>
                                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Any additional information about this stock movement">{{ old('notes') }}</textarea>
                                    @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                        Cancel
                                    </a>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Update Stock
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product Info & Current Stock -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Product Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Product Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-gray-500">Product Name</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-xs text-gray-500">SKU</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $product->sku }}</p>
                                </div>
                                
                                @if($product->category)
                                <div>
                                    <p class="text-xs text-gray-500">Category</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $product->category->name }}</p>
                                </div>
                                @endif
                                
                                @if($product->brand)
                                <div>
                                    <p class="text-xs text-gray-500">Brand</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $product->brand->name }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Current Stock Levels -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Stock Levels</h3>
                            
                            @if($product->inventory->count() > 0)
                            <div class="space-y-4">
                                @foreach($product->inventory as $inv)
                                <div class="border-l-4 @if($inv->is_out_of_stock) border-red-500 @elseif($inv->is_low_stock) border-yellow-500 @else border-green-500 @endif pl-4 py-2">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                @if($inv->variant)
                                                {{ $inv->variant->name }}
                                                @else
                                                Base Product
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $inv->location)) }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($inv->is_out_of_stock) bg-red-100 text-red-800
                                            @elseif($inv->is_low_stock) bg-yellow-100 text-yellow-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ ucwords(str_replace('_', ' ', $inv->stock_status)) }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <p class="text-gray-500">Available</p>
                                            <p class="font-semibold text-gray-900">{{ $inv->quantity_available }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Reserved</p>
                                            <p class="font-semibold text-gray-900">{{ $inv->quantity_reserved }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Reorder Level</p>
                                            <p class="font-semibold text-gray-900">{{ $inv->reorder_level }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Sold</p>
                                            <p class="font-semibold text-gray-900">{{ $inv->quantity_sold }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-gray-500">No inventory records found</p>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <!-- Recent Movements -->
            @if($recentMovements->count() > 0)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Stock Movements</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentMovements as $movement)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $movement->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($movement->movement_direction === 'in') bg-green-100 text-green-800
                                            @elseif($movement->movement_direction === 'out') bg-red-100 text-red-800
                                            @else bg-blue-100 text-blue-800
                                            @endif">
                                            {{ $movement->movement_type_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium 
                                            @if($movement->quantity > 0) text-green-600
                                            @else text-red-600
                                            @endif">
                                            @if($movement->quantity > 0)+@endif{{ $movement->quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $movement->location_display }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $movement->performedBy?->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $movement->notes ?? '-' }}
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

    @push('scripts')
    <script>
        // Handle reason code selection
        document.addEventListener('DOMContentLoaded', function() {
            const reasonSelect = document.getElementById('reason_select');
            const reasonInput = document.getElementById('reason');

            if (reasonSelect && reasonInput) {
                reasonSelect.addEventListener('change', function() {
                    if (this.value && this.value !== 'custom') {
                        reasonInput.value = this.value;
                    } else if (this.value === 'custom') {
                        reasonInput.value = '';
                        reasonInput.focus();
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
