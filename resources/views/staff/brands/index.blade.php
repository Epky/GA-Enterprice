<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Brand Management') }}
            </h2>
            <a href="{{ route('staff.brands.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-plus mr-2"></i>Add Brand
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif

            <!-- Search and Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('staff.brands.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                <input type="text" name="search" value="{{ request('search') }}" 
                                    placeholder="Search brands..." 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Products</label>
                                <select name="has_products" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Brands</option>
                                    <option value="1" {{ request('has_products') === '1' ? 'selected' : '' }}>With Products</option>
                                    <option value="0" {{ request('has_products') === '0' ? 'selected' : '' }}>Without Products</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-search mr-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Brands Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($brands as $brand)
                            <div class="border rounded-lg p-6 hover:shadow-lg transition">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center">
                                        @if($brand->display_logo)
                                            <img src="{{ asset('storage/' . $brand->display_logo) }}" alt="{{ $brand->name }}" class="h-16 w-16 object-contain mr-4 shadow-sm">
                                        @else
                                            <div class="h-16 w-16 rounded bg-gray-200 flex items-center justify-center mr-4">
                                                <i class="fas fa-tag text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $brand->name }}</h3>
                                            <p class="text-sm text-gray-500">{{ $brand->slug }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $brand->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                @if($brand->description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $brand->description }}</p>
                                @endif

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-blue-50 p-3 rounded">
                                        <div class="text-2xl font-bold text-blue-600">{{ $brand->products_count ?? 0 }}</div>
                                        <div class="text-xs text-gray-600">Total Products</div>
                                    </div>
                                    <div class="bg-green-50 p-3 rounded">
                                        <div class="text-2xl font-bold text-green-600">{{ $brand->active_products_count ?? 0 }}</div>
                                        <div class="text-xs text-gray-600">Active Products</div>
                                    </div>
                                </div>

                                @if($brand->website_url)
                                    <div class="mb-4">
                                        <a href="{{ $brand->website_url }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-external-link-alt mr-1"></i>Visit Website
                                        </a>
                                    </div>
                                @endif

                                <div class="flex justify-between items-center pt-4 border-t">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('staff.brands.show', $brand) }}" 
                                            class="text-blue-600 hover:text-blue-900" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('staff.brands.edit', $brand) }}" 
                                            class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('staff.brands.toggle-status', $brand) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                                                <i class="fas fa-toggle-{{ $brand->is_active ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <form action="{{ route('staff.brands.destroy', $brand) }}" method="POST" class="inline" 
                                        onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 text-center py-12">
                                <i class="fas fa-tag text-gray-300 text-6xl mb-4"></i>
                                <p class="text-gray-500 mb-4">No brands found.</p>
                                <a href="{{ route('staff.brands.create') }}" class="text-blue-600 hover:text-blue-800">
                                    Create your first brand
                                </a>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $brands->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
