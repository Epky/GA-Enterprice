<x-customer-layout>
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-white mb-4">Shop All Products</h1>
            <p class="text-white/90">Browse our complete collection of beauty products</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Search and Filter -->
        <div class="mb-8">
            <form action="{{ route('products.index') }}" method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search products..." 
                       value="{{ request('search') }}"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-2 rounded-lg hover:shadow-lg transition">
                    Search
                </button>
            </form>
        </div>

        <!-- Categories -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Categories</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('products.index') }}" 
                   class="px-4 py-2 rounded-full {{ !request('category') ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }} transition">
                    All Products
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('products.category', $cat) }}" 
                       class="px-4 py-2 rounded-full bg-white text-gray-700 hover:bg-gray-100 transition">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                @foreach($products as $product)
                    <a href="{{ route('products.show', $product) }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <div class="aspect-square bg-gray-200 overflow-hidden">
                            @if($product->images->count() > 0)
                                <img src="{{ Storage::url($product->images->first()->image_url) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    No Image
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $product->name }}</h3>
                            <p class="text-sm text-gray-500 mb-2">{{ $product->brand->name ?? 'No Brand' }}</p>
                            <p class="text-lg font-bold text-purple-600">₱{{ number_format($product->base_price, 2) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No products found.</p>
            </div>
        @endif
    </div>
</div>
</x-customer-layout>
