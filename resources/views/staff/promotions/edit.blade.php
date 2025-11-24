<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Promotion') }} - {{ $promotion->name }}
            </h2>
            <a href="{{ route('staff.promotions.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('staff.promotions.update', $promotion) }}">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Basic Information</h3>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Promotion Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $promotion->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">
                                    Description
                                </label>
                                <textarea name="description" id="description" rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $promotion->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Discount Configuration</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="promotion_type" class="block text-sm font-medium text-gray-700">
                                    Promotion Type <span class="text-red-500">*</span>
                                </label>
                                <select name="promotion_type" id="promotion_type" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        onchange="toggleDiscountValue()" required>
                                    <option value="percentage" {{ old('promotion_type', $promotion->promotion_type) == 'percentage' ? 'selected' : '' }}>Percentage Discount</option>
                                    <option value="fixed_amount" {{ old('promotion_type', $promotion->promotion_type) == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount Discount</option>
                                    <option value="bogo" {{ old('promotion_type', $promotion->promotion_type) == 'bogo' ? 'selected' : '' }}>Buy One Get One</option>
                                    <option value="free_shipping" {{ old('promotion_type', $promotion->promotion_type) == 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                                </select>
                            </div>

                            <div id="discount-value-field">
                                <label for="discount_value" class="block text-sm font-medium text-gray-700">
                                    Discount Value <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" name="discount_value" id="discount_value" 
                                       value="{{ old('discount_value', $promotion->discount_value) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500" id="discount-hint">Enter percentage (e.g., 20 for 20%)</p>
                            </div>

                            <div>
                                <label for="min_purchase_amount" class="block text-sm font-medium text-gray-700">
                                    Minimum Purchase Amount
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" step="0.01" name="min_purchase_amount" id="min_purchase_amount" 
                                           value="{{ old('min_purchase_amount', $promotion->min_purchase_amount) }}"
                                           class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="max_discount_amount" class="block text-sm font-medium text-gray-700">
                                    Maximum Discount Amount
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" step="0.01" name="max_discount_amount" id="max_discount_amount" 
                                           value="{{ old('max_discount_amount', $promotion->max_discount_amount) }}"
                                           class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Applicability</h3>

                        <div class="space-y-4">
                            <div>
                                <label for="applicable_to" class="block text-sm font-medium text-gray-700">
                                    Apply To <span class="text-red-500">*</span>
                                </label>
                                <select name="applicable_to" id="applicable_to" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        onchange="toggleApplicableIds()" required>
                                    <option value="all" {{ old('applicable_to', $promotion->applicable_to) == 'all' ? 'selected' : '' }}>All Products</option>
                                    <option value="category" {{ old('applicable_to', $promotion->applicable_to) == 'category' ? 'selected' : '' }}>Specific Categories</option>
                                    <option value="brand" {{ old('applicable_to', $promotion->applicable_to) == 'brand' ? 'selected' : '' }}>Specific Brands</option>
                                    <option value="product" {{ old('applicable_to', $promotion->applicable_to) == 'product' ? 'selected' : '' }}>Specific Products</option>
                                </select>
                            </div>

                            <div id="applicable-ids-field" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Items <span class="text-red-500">*</span>
                                </label>
                                
                                @php
                                    $selectedIds = old('applicable_ids', $promotion->applicable_ids ?? []);
                                @endphp

                                <div id="category-select" class="hidden">
                                    <select name="applicable_ids[]" multiple size="8"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ in_array($category->id, $selectedIds) ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="brand-select" class="hidden">
                                    <select name="applicable_ids[]" multiple size="8"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ in_array($brand->id, $selectedIds) ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="product-select" class="hidden">
                                    <select name="applicable_ids[]" multiple size="8"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ in_array($product->id, $selectedIds) ? 'selected' : '' }}>
                                                {{ $product->name }} ({{ $product->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple items</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Schedule & Limits</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="start_date" id="start_date" 
                                       value="{{ old('start_date', $promotion->start_date->format('Y-m-d\TH:i')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                            </div>

                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">
                                    End Date <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="end_date" id="end_date" 
                                       value="{{ old('end_date', $promotion->end_date->format('Y-m-d\TH:i')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                            </div>

                            <div>
                                <label for="usage_limit" class="block text-sm font-medium text-gray-700">
                                    Usage Limit
                                </label>
                                <input type="number" name="usage_limit" id="usage_limit" 
                                       value="{{ old('usage_limit', $promotion->usage_limit) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Leave empty for unlimited usage</p>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" 
                                       {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Update Promotion
                    </button>
                    <a href="{{ route('staff.promotions.show', $promotion) }}" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleDiscountValue() {
            const promotionType = document.getElementById('promotion_type').value;
            const discountField = document.getElementById('discount-value-field');
            const discountInput = document.getElementById('discount_value');
            const discountHint = document.getElementById('discount-hint');

            if (promotionType === 'free_shipping' || promotionType === 'bogo') {
                discountField.classList.add('hidden');
                discountInput.removeAttribute('required');
            } else {
                discountField.classList.remove('hidden');
                discountInput.setAttribute('required', 'required');
                
                if (promotionType === 'percentage') {
                    discountHint.textContent = 'Enter percentage (e.g., 20 for 20%)';
                } else {
                    discountHint.textContent = 'Enter fixed amount (e.g., 10 for $10 off)';
                }
            }
        }

        function toggleApplicableIds() {
            const applicableTo = document.getElementById('applicable_to').value;
            const applicableIdsField = document.getElementById('applicable-ids-field');
            const categorySelect = document.getElementById('category-select');
            const brandSelect = document.getElementById('brand-select');
            const productSelect = document.getElementById('product-select');

            // Hide all selects first
            categorySelect.classList.add('hidden');
            brandSelect.classList.add('hidden');
            productSelect.classList.add('hidden');

            if (applicableTo === 'all') {
                applicableIdsField.classList.add('hidden');
            } else {
                applicableIdsField.classList.remove('hidden');
                
                if (applicableTo === 'category') {
                    categorySelect.classList.remove('hidden');
                } else if (applicableTo === 'brand') {
                    brandSelect.classList.remove('hidden');
                } else if (applicableTo === 'product') {
                    productSelect.classList.remove('hidden');
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleDiscountValue();
            toggleApplicableIds();
        });
    </script>
    @endpush
</x-app-layout>
