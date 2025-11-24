<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Category Details') }}: {{ $category->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('staff.categories.edit', $category) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('staff.categories.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
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

            <!-- Category Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                            
                            @if($category->image_url)
                                <div class="mb-4">
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-48 w-48 object-cover rounded">
                                </div>
                            @endif

                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name:</span>
                                    <p class="text-gray-900">{{ $category->name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Slug:</span>
                                    <p class="text-gray-900">{{ $category->slug }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Parent Category:</span>
                                    <p class="text-gray-900">
                                        @if($category->parent)
                                            <a href="{{ route('staff.categories.show', $category->parent) }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $category->parent->name }}
                                            </a>
                                        @else
                                            Root Category
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status:</span>
                                    <p>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Display Order:</span>
                                    <p class="text-gray-900">{{ $category->display_order }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Description:</span>
                                    <p class="text-gray-900">{{ $category->description ?: 'No description' }}</p>
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
                                    <div class="text-2xl font-bold text-green-600">{{ $stats['direct_products'] }}</div>
                                    <div class="text-sm text-gray-600">Direct Products</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600">{{ $stats['active_products'] }}</div>
                                    <div class="text-sm text-gray-600">Active Products</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['child_categories'] }}</div>
                                    <div class="text-sm text-gray-600">Subcategories</div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="text-md font-semibold mb-2">SEO Information</h4>
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Meta Title:</span>
                                        <p class="text-gray-900 text-sm">{{ $category->meta_title ?: 'Not set' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Meta Description:</span>
                                        <p class="text-gray-900 text-sm">{{ $category->meta_description ?: 'Not set' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subcategories -->
            @if($category->children->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Subcategories ({{ $category->children->count() }})</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($category->children as $child)
                                <div class="border rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold">{{ $child->name }}</h4>
                                        <span class="px-2 text-xs rounded-full {{ $child->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $child->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">{{ $child->products->count() }} products</p>
                                    <a href="{{ route('staff.categories.show', $child) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                        View Details →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Products -->
            @if($category->products->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Products ({{ $category->products->count() }})</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($category->products as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $product->sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $product->brand ? $product->brand->name : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format($product->base_price, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $product->inventory ? $product->inventory->quantity_available : 0 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($product->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('staff.products.show', $product) }}" class="text-blue-600 hover:text-blue-900">
                                                    View
                                                </a>
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
</x-app-layout>
