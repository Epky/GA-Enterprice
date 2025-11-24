@extends('shop.index')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-white mb-4">{{ $category->name }}</h1>
            <p class="text-white/90">{{ $category->description ?? 'Browse our ' . $category->name . ' collection' }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <a href="{{ route('products.index') }}" class="text-purple-600 hover:text-purple-800">
                ← Back to All Products
            </a>
        </div>

        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <a href="{{ route('products.show', $product) }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <div class="aspect-square bg-gray-200 overflow-hidden">
                            @if($product->images->count() > 0)
                                <img src="{{ Storage::url($product->images->first()->image_url) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $product->name }}</h3>
                            <p class="text-sm text-gray-500 mb-2">{{ $product->brand->name ?? 'No Brand' }}</p>
                            <p class="text-lg font-bold text-purple-600">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No products in this category yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
