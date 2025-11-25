<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Product Visibility Management') }}
            </h2>
            <a href="{{ route('staff.products.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Products
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Active Products</div>
                    <div class="text-3xl font-bold text-green-600">{{ $stats['active'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Inactive Products</div>
                    <div class="text-3xl font-bold text-gray-600">{{ $stats['inactive'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Featured Products</div>
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['featured'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Out of Stock</div>
                    <div class="text-3xl font-bold text-red-600">{{ $stats['out_of_stock'] }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">New Arrivals</div>
                    <div class="text-3xl font-bold text-purple-600">{{ $stats['new_arrivals'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Best Sellers</div>
                    <div class="text-3xl font-bold text-yellow-600">{{ $stats['best_sellers'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600">Discontinued</div>
                    <div class="text-3xl font-bold text-gray-400">{{ $stats['discontinued'] }}</div>
                </div>
            </div>

            <!-- Bulk Actions Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Bulk Visibility Actions</h3>
                    <form id="bulkVisibilityForm" method="POST" action="{{ route('staff.products.visibility.bulk-update') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- No Change --</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                    <option value="discontinued">Discontinued</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Featured</label>
                                <select name="is_featured" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- No Change --</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Arrival</label>
                                <select name="is_new_arrival" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- No Change --</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Best Seller</label>
                                <select name="is_best_seller" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- No Change --</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" disabled id="bulkActionBtn">
                            Apply to Selected Products
                        </button>
                        <span id="selectedCount" class="ml-4 text-sm text-gray-600">0 products selected</span>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Visibility Flags
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stock
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <input type="checkbox" name="products[]" value="{{ $product->id }}" class="product-checkbox rounded border-gray-300">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                @if ($product->primaryImage)
                                                    <img src="{{ $product->primaryImage->full_url }}" alt="{{ $product->name }}" class="h-10 w-10 rounded object-cover mr-3">
                                                @else
                                                    <div class="h-10 w-10 bg-gray-200 rounded mr-3 flex items-center justify-center">
                                                        <span class="text-gray-400 text-xs">No Image</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                    <div class="text-sm text-gray-500">SKU: {{ $product->sku }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <select class="status-select text-sm rounded-md border-gray-300" data-product-id="{{ $product->id }}">
                                                <option value="active" {{ $product->status === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $product->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                <option value="out_of_stock" {{ $product->status === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                                <option value="discontinued" {{ $product->status === 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button class="toggle-flag px-2 py-1 text-xs rounded {{ $product->is_featured ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}" 
                                                        data-product-id="{{ $product->id }}" 
                                                        data-flag="is_featured">
                                                    Featured
                                                </button>
                                                <button class="toggle-flag px-2 py-1 text-xs rounded {{ $product->is_new_arrival ? 'bg-purple-500 text-white' : 'bg-gray-200 text-gray-700' }}" 
                                                        data-product-id="{{ $product->id }}" 
                                                        data-flag="is_new_arrival">
                                                    New
                                                </button>
                                                <button class="toggle-flag px-2 py-1 text-xs rounded {{ $product->is_best_seller ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700' }}" 
                                                        data-product-id="{{ $product->id }}" 
                                                        data-flag="is_best_seller">
                                                    Best Seller
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $totalStock = $product->inventory->sum('quantity_available');
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $totalStock > 10 ? 'bg-green-100 text-green-800' : ($totalStock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $totalStock }} units
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <a href="{{ route('staff.products.preview', $product) }}" 
                                               class="text-blue-600 hover:text-blue-900 mr-3" 
                                               target="_blank">
                                                Preview
                                            </a>
                                            <a href="{{ route('staff.products.edit', $product) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No products found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @vite(['resources/js/visibility-manager.js'])
    @endpush
</x-app-layout>
