@props(['products', 'period' => 'month'])

<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-shadow duration-300">
    <div class="p-6">
        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent mb-4">Top Selling Products</h3>
        
        @if($products && $products->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-purple-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">
                                Rank
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-purple-700 uppercase tracking-wider">
                                Product Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-purple-700 uppercase tracking-wider">
                                Quantity Sold
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-purple-700 uppercase tracking-wider">
                                Revenue
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($products as $index => $product)
                            <tr class="hover:bg-gradient-to-r hover:from-pink-50 hover:to-purple-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                                        @if($index === 0) bg-gradient-to-br from-pink-400 to-pink-600 text-white
                                        @elseif($index === 1) bg-gradient-to-br from-purple-400 to-purple-600 text-white
                                        @elseif($index === 2) bg-gradient-to-br from-indigo-400 to-indigo-600 text-white
                                        @else bg-gray-50 text-gray-600
                                        @endif
                                        font-semibold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $product->product_name ?? $product->name ?? 'Unknown Product' }}</div>
                                    @if(isset($product->category_name))
                                        <div class="text-xs text-gray-500">{{ $product->category_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-medium">
                                    {{ number_format($product->total_quantity ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-semibold">
                                    ₱{{ number_format($product->total_revenue ?? 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No product sales data available for this period.</p>
            </div>
        @endif
    </div>
</div>
