<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $promotion->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('staff.promotions.edit', $promotion) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Edit
                </a>
                <a href="{{ route('staff.promotions.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Promotion Details</h3>
                            
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'scheduled' => 'bg-blue-100 text-blue-800',
                                    'expired' => 'bg-gray-100 text-gray-800',
                                    'inactive' => 'bg-red-100 text-red-800',
                                    'limit_reached' => 'bg-yellow-100 text-yellow-800',
                                ];
                                $statusColor = $statusColors[$promotion->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            
                            <div class="mb-4">
                                <span class="px-3 py-1 text-sm rounded {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $promotion->status)) }}
                                </span>
                            </div>

                            @if($promotion->description)
                                <p class="text-gray-700 mb-4">{{ $promotion->description }}</p>
                            @endif

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Type</p>
                                    <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $promotion->promotion_type)) }}</p>
                                </div>

                                <div>
                                    <p class="text-gray-600">Discount Value</p>
                                    <p class="font-semibold">
                                        @if($promotion->promotion_type === 'percentage')
                                            {{ $promotion->discount_value }}%
                                        @elseif($promotion->promotion_type === 'fixed_amount')
                                            ${{ number_format($promotion->discount_value, 2) }}
                                        @elseif($promotion->promotion_type === 'bogo')
                                            Buy 1 Get 1
                                        @else
                                            Free Shipping
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-600">Applies To</p>
                                    <p class="font-semibold">{{ ucfirst($promotion->applicable_to) }}</p>
                                </div>

                                @if($promotion->min_purchase_amount)
                                    <div>
                                        <p class="text-gray-600">Minimum Purchase</p>
                                        <p class="font-semibold">${{ number_format($promotion->min_purchase_amount, 2) }}</p>
                                    </div>
                                @endif

                                @if($promotion->max_discount_amount)
                                    <div>
                                        <p class="text-gray-600">Maximum Discount</p>
                                        <p class="font-semibold">${{ number_format($promotion->max_discount_amount, 2) }}</p>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-gray-600">Start Date</p>
                                    <p class="font-semibold">{{ $promotion->start_date->format('M d, Y H:i') }}</p>
                                </div>

                                <div>
                                    <p class="text-gray-600">End Date</p>
                                    <p class="font-semibold">{{ $promotion->end_date->format('M d, Y H:i') }}</p>
                                </div>

                                <div>
                                    <p class="text-gray-600">Usage</p>
                                    <p class="font-semibold">
                                        {{ $promotion->usage_count }}
                                        @if($promotion->usage_limit)
                                            / {{ $promotion->usage_limit }}
                                        @else
                                            (Unlimited)
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Affected Products -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Affected Products ({{ $stats['affected_products_count'] }})
                            </h3>

                            @if($affectedProducts->count() > 0)
                                <div class="space-y-3">
                                    @foreach($affectedProducts as $product)
                                        <div class="flex justify-between items-center border-b pb-2">
                                            <div>
                                                <p class="font-medium">{{ $product->name }}</p>
                                                <p class="text-xs text-gray-600">SKU: {{ $product->sku }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-600">Base: ${{ number_format($product->base_price, 2) }}</p>
                                                <p class="text-sm font-semibold text-green-600">
                                                    Discount: ${{ number_format($promotion->calculateDiscount($product->base_price), 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($stats['affected_products_count'] > 10)
                                        <p class="text-sm text-gray-600 text-center pt-2">
                                            Showing 10 of {{ $stats['affected_products_count'] }} products
                                        </p>
                                    @endif
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">No products affected by this promotion</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-4">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Statistics</h3>

                            <div class="space-y-4">
                                <div class="border-b pb-3">
                                    <p class="text-sm text-gray-600">Affected Products</p>
                                    <p class="text-2xl font-bold">{{ $stats['affected_products_count'] }}</p>
                                </div>

                                <div class="border-b pb-3">
                                    <p class="text-sm text-gray-600">Times Used</p>
                                    <p class="text-2xl font-bold">{{ $stats['usage_count'] }}</p>
                                </div>

                                @if($stats['usage_remaining'] !== null)
                                    <div class="border-b pb-3">
                                        <p class="text-sm text-gray-600">Usage Remaining</p>
                                        <p class="text-2xl font-bold">{{ $stats['usage_remaining'] }}</p>
                                    </div>
                                @endif

                                <div class="border-b pb-3">
                                    <p class="text-sm text-gray-600">Average Discount</p>
                                    <p class="text-2xl font-bold">${{ number_format($stats['average_discount'], 2) }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <p class="text-lg font-semibold">{{ ucfirst(str_replace('_', ' ', $stats['status'])) }}</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-2">
                                <form method="POST" action="{{ route('staff.promotions.duplicate', $promotion) }}">
                                    @csrf
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Duplicate
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('staff.promotions.destroy', $promotion) }}" 
                                      onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
