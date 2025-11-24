<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Brand Details') }}: {{ $brand->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('staff.brands.edit', $brand) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('staff.brands.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Brand Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                            
                            @if($brand->logo_url)
                                <div class="mb-4">
                                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="h-32 w-32 object-contain">
                                </div>
                            @endif

                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name:</span>
                                    <p class="text-gray-900">{{ $brand->name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Slug:</span>
                                    <p class="text-gray-900">{{ $brand->slug }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status:</span>
                                    <p>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $brand->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                                @if($brand->website_url)
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Website:</span>
                                        <p>
                                            <a href="{{ $brand->website_url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                {{ $brand->website_url }} <i class="fas fa-external-link-alt text-xs"></i>
                                            </a>
                                        </p>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Description:</span>
                                    <p class="text-gray-900">{{ $brand->description ?: 'No description' }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold mb-4">Statistics</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_products'] }}</div>
                                    <div class="text-sm text-gray-600">Total Products</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ $stats['active_products'] }}</div>
                                    <div class="text-sm text-gray-600">Active Products</div>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">{{ $stats['inactive_products'] }}</div>
                                    <div class="text-sm text-gray-600">Inactive Products</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['low_stock_products'] }}</div>
                                    <div class="text-sm text-gray-600">Low Stock</div>
                                </div>
                            </div>

                            <div class="mt-6 bg-purple-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600 mb-1">Total Inventory Value</div>
                                <div class="text-2xl font-bold text-purple-600">${{ number_format($stats['total_inventory_value'], 2) }}</div>
                            </div>

                            <div class="mt-6">
                                <h4 class="text-md font-semibold mb-2">SEO Information</h4>
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Meta Title:</span>
                                        <p class="text-gray-900 text-sm">{{ $brand->meta_title ?: 'Not set' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Meta Description:</span>
                                        <p class="text-gray-900 text-sm">{{ $brand->meta_description ?: 'Not set' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            @if($recentProducts->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Recent Products</h3>
                            <a href="{{ route('staff.products.index', ['brand' => $brand->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                View All Products →
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentProducts as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $product->sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $product->category ? $product->category->name : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format($product->base_price, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">
                                                    {{ $product->inventory ? $product->inventory->quantity_available : 0 }}
                                                </span>
                                                @if($product->inventory && $product->inventory->quantity_available <= $product->inventory->reorder_level)
                                                    <span class="ml-1 text-xs text-red-600">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($product->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('staff.products.show', $product) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('staff.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <i class="fas fa-box-open text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 mb-4">No products found for this brand.</p>
                        <a href="{{ route('staff.products.create', ['brand' => $brand->id]) }}" class="text-blue-600 hover:text-blue-800">
                            Add your first product
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
