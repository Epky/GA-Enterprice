<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bulk Update Pricing') }} - {{ count($products) }} Products
            </h2>
            <a href="{{ route('staff.pricing.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Update Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Pricing Update Options</h3>

                            <form method="POST" action="{{ route('staff.pricing.bulk-update') }}" id="bulk-update-form">
                                @csrf

                                @foreach($productIds as $id)
                                    <input type="hidden" name="product_ids[]" value="{{ $id }}">
                                @endforeach

                                <!-- Update Type Selection -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Update Type</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="update_type" value="direct" checked 
                                                   class="rounded" onchange="toggleUpdateType()">
                                            <span class="ml-2">Direct Price Update</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="update_type" value="adjustment" 
                                                   class="rounded" onchange="toggleUpdateType()">
                                            <span class="ml-2">Price Adjustment (Percentage or Fixed Amount)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Direct Update Fields -->
                                <div id="direct-fields" class="space-y-4">
                                    <h4 class="font-medium text-gray-700">Set New Prices</h4>
                                    <p class="text-sm text-gray-600">Leave fields empty to keep existing values</p>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label for="base_price" class="block text-sm font-medium text-gray-700">
                                                Base Price
                                            </label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input type="number" step="0.01" name="base_price" id="base_price" 
                                                       class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="sale_price" class="block text-sm font-medium text-gray-700">
                                                Sale Price
                                            </label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input type="number" step="0.01" name="sale_price" id="sale_price" 
                                                       class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="cost_price" class="block text-sm font-medium text-gray-700">
                                                Cost Price
                                            </label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input type="number" step="0.01" name="cost_price" id="cost_price" 
                                                       class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Adjustment Fields -->
                                <div id="adjustment-fields" class="space-y-4 hidden">
                                    <h4 class="font-medium text-gray-700">Price Adjustment</h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Adjustment Type
                                            </label>
                                            <select name="adjustment_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Fixed Amount ($)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Adjustment Value
                                            </label>
                                            <input type="number" step="0.01" name="adjustment_value" 
                                                   placeholder="e.g., 10 for 10% or $10"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <p class="mt-1 text-xs text-gray-500">Use negative values to decrease prices</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Apply To
                                        </label>
                                        <select name="apply_to" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="base_price">Base Price Only</option>
                                            <option value="sale_price">Sale Price Only</option>
                                            <option value="both">Both Base and Sale Price</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-6 flex gap-4">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                        Update {{ count($products) }} Products
                                    </button>
                                    <a href="{{ route('staff.pricing.index') }}" 
                                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Selected Products Preview -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-4">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Selected Products ({{ count($products) }})</h3>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach($products as $product)
                                    <div class="border-b pb-3">
                                        <p class="font-medium text-sm">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-600">SKU: {{ $product->sku }}</p>
                                        <div class="mt-1 text-xs">
                                            <span class="text-gray-600">Base:</span>
                                            <span class="font-semibold">${{ number_format($product->base_price, 2) }}</span>
                                            @if($product->sale_price)
                                                <span class="text-gray-600 ml-2">Sale:</span>
                                                <span class="font-semibold text-red-600">${{ number_format($product->sale_price, 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleUpdateType() {
            const updateType = document.querySelector('input[name="update_type"]:checked').value;
            const directFields = document.getElementById('direct-fields');
            const adjustmentFields = document.getElementById('adjustment-fields');

            if (updateType === 'direct') {
                directFields.classList.remove('hidden');
                adjustmentFields.classList.add('hidden');
                
                // Clear adjustment fields
                document.querySelector('[name="adjustment_type"]').value = 'percentage';
                document.querySelector('[name="adjustment_value"]').value = '';
                document.querySelector('[name="apply_to"]').value = 'base_price';
            } else {
                directFields.classList.add('hidden');
                adjustmentFields.classList.remove('hidden');
                
                // Clear direct fields
                document.getElementById('base_price').value = '';
                document.getElementById('sale_price').value = '';
                document.getElementById('cost_price').value = '';
            }
        }

        // Form validation
        document.getElementById('bulk-update-form').addEventListener('submit', function(e) {
            const updateType = document.querySelector('input[name="update_type"]:checked').value;
            
            if (updateType === 'direct') {
                const basePrice = document.getElementById('base_price').value;
                const salePrice = document.getElementById('sale_price').value;
                const costPrice = document.getElementById('cost_price').value;
                
                if (!basePrice && !salePrice && !costPrice) {
                    e.preventDefault();
                    alert('Please enter at least one price value to update.');
                    return false;
                }
            } else {
                const adjustmentValue = document.querySelector('[name="adjustment_value"]').value;
                
                if (!adjustmentValue) {
                    e.preventDefault();
                    alert('Please enter an adjustment value.');
                    return false;
                }
            }
            
            return confirm(`Are you sure you want to update pricing for ${{{ count($products) }}} products?`);
        });
    </script>
    @endpush
</x-app-layout>
