@props(['categories', 'title' => 'Sales by Category'])

<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-shadow duration-300">
    <div class="p-6">
        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent mb-4">{{ $title }}</h3>
        
        @if($categories && $categories->count() > 0)
            <div class="space-y-4">
                @foreach($categories as $category)
                    <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">
                                    {{ $category->category_name ?? $category->name ?? 'Unknown Category' }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if(isset($category->order_count))
                                        {{ number_format($category->order_count) }} orders
                                    @endif
                                </p>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-sm font-semibold text-gray-900">
                                    ₱{{ number_format($category->total_revenue ?? 0, 2) }}
                                </p>
                                @if(isset($category->percentage))
                                    <p class="text-xs text-gray-500">
                                        {{ number_format($category->percentage, 1) }}%
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        @if(isset($category->percentage))
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ min($category->percentage, 100) }}%">
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if($categories->count() > 5)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        Showing top {{ $categories->count() }} categories
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No category sales data available for this period.</p>
            </div>
        @endif
    </div>
</div>
