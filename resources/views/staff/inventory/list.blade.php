<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory List') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('staff.inventory.bulk-update.form') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Bulk Update
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('staff.inventory.list') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Product name or SKU" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>

                        <!-- Stock Status -->
                        <div>
                            <label for="stock_status" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                            <select id="stock_status" name="stock_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">All Status</option>
                                <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
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
                            </select>
                        </div>

                        <!-- Filter Button -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($inventory as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="inventory-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                                               data-product-id="{{ $item->product_id }}" 
                                               data-variant-id="{{ $item->variant_id }}" 
                                               data-location="{{ $item->location }}">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                        @if($item->variant)
                                        <div class="text-xs text-gray-500">{{ $item->variant->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->product->sku }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ucwords(str_replace('_', ' ', $item->location)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{{ $item->quantity_available }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->quantity_reserved }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->reorder_level }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->stock_status === 'out_of_stock')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Out of Stock
                                        </span>
                                        @elseif($item->stock_status === 'low_stock')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Low Stock
                                        </span>
                                        @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            In Stock
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('staff.inventory.edit', $item->product_id) }}" class="text-blue-600 hover:text-blue-900">
                                            Update
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No inventory items found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($inventory->hasPages())
                    <div class="mt-6">
                        {{ $inventory->links() }}
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div id="bulkUpdateModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="bulkUpdateForm" method="POST" action="{{ route('staff.inventory.bulk-update') }}">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                    Bulk Stock Update
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Movement Type -->
                                    <div>
                                        <label for="bulk_movement_type" class="block text-sm font-medium text-gray-700 mb-1">
                                            Movement Type <span class="text-red-500">*</span>
                                        </label>
                                        <select id="bulk_movement_type" name="movement_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select movement type</option>
                                            <option value="purchase">Purchase/Restock (Add Stock)</option>
                                            <option value="sale">Sale (Remove Stock)</option>
                                            <option value="return">Return (Add Stock)</option>
                                            <option value="damage">Damage/Loss (Remove Stock)</option>
                                            <option value="adjustment">Adjustment (Add/Remove)</option>
                                        </select>
                                    </div>

                                    <!-- Quantity -->
                                    <div>
                                        <label for="bulk_quantity" class="block text-sm font-medium text-gray-700 mb-1">
                                            Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="bulk_quantity" name="quantity" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter quantity">
                                        <p class="mt-1 text-xs text-gray-500">This quantity will be applied to all selected items</p>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label for="bulk_notes" class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea id="bulk_notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Reason for bulk update"></textarea>
                                    </div>

                                    <input type="hidden" id="bulk_updates_data" name="updates">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Update Stock
                        </button>
                        <button type="button" id="cancelBulkUpdate" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const inventoryCheckboxes = document.querySelectorAll('.inventory-checkbox');
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            const selectedCountSpan = document.getElementById('selectedCount');
            const bulkUpdateModal = document.getElementById('bulkUpdateModal');
            const cancelBulkUpdate = document.getElementById('cancelBulkUpdate');
            const bulkUpdateForm = document.getElementById('bulkUpdateForm');

            // Select all functionality
            selectAllCheckbox.addEventListener('change', function() {
                inventoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectedCount();
            });

            // Individual checkbox change
            inventoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            function updateSelectedCount() {
                const selectedCount = document.querySelectorAll('.inventory-checkbox:checked').length;
                selectedCountSpan.textContent = selectedCount;
                bulkUpdateBtn.disabled = selectedCount === 0;
            }

            // Show bulk update modal
            bulkUpdateBtn.addEventListener('click', function() {
                bulkUpdateModal.classList.remove('hidden');
            });

            // Hide bulk update modal
            cancelBulkUpdate.addEventListener('click', function() {
                bulkUpdateModal.classList.add('hidden');
            });

            // Handle bulk update form submission
            bulkUpdateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const selectedItems = [];
                document.querySelectorAll('.inventory-checkbox:checked').forEach(checkbox => {
                    selectedItems.push({
                        product_id: checkbox.dataset.productId,
                        variant_id: checkbox.dataset.variantId || null,
                        location: checkbox.dataset.location,
                        quantity: document.getElementById('bulk_quantity').value,
                        movement_type: document.getElementById('bulk_movement_type').value,
                        notes: document.getElementById('bulk_notes').value
                    });
                });

                document.getElementById('bulk_updates_data').value = JSON.stringify(selectedItems);
                this.submit();
            });
        });
    </script>
    @endpush
</x-app-layout>
