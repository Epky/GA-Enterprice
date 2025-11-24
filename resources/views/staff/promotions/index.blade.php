<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Promotions') }}
            </h2>
            <a href="{{ route('staff.promotions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create Promotion
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

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('staff.promotions.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="promotion_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                <option value="percentage" {{ request('promotion_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed_amount" {{ request('promotion_type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                                <option value="bogo" {{ request('promotion_type') == 'bogo' ? 'selected' : '' }}>BOGO</option>
                                <option value="free_shipping" {{ request('promotion_type') == 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Promotions List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($promotions as $promotion)
                            <div class="border rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-semibold">{{ $promotion->name }}</h3>
                                            
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
                                            
                                            <span class="px-2 py-1 text-xs rounded {{ $statusColor }}">
                                                {{ ucfirst(str_replace('_', ' ', $promotion->status)) }}
                                            </span>
                                            
                                            <span class="px-2 py-1 text-xs rounded bg-purple-100 text-purple-800">
                                                {{ ucfirst(str_replace('_', ' ', $promotion->promotion_type)) }}
                                            </span>
                                        </div>
                                        
                                        @if($promotion->description)
                                            <p class="text-sm text-gray-600 mt-2">{{ $promotion->description }}</p>
                                        @endif
                                        
                                        <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600">Discount:</span>
                                                <span class="font-semibold ml-1">
                                                    @if($promotion->promotion_type === 'percentage')
                                                        {{ $promotion->discount_value }}%
                                                    @elseif($promotion->promotion_type === 'fixed_amount')
                                                        ${{ number_format($promotion->discount_value, 2) }}
                                                    @elseif($promotion->promotion_type === 'bogo')
                                                        Buy 1 Get 1
                                                    @else
                                                        Free Shipping
                                                    @endif
                                                </span>
                                            </div>
                                            
                                            <div>
                                                <span class="text-gray-600">Applies to:</span>
                                                <span class="font-semibold ml-1">{{ ucfirst($promotion->applicable_to) }}</span>
                                            </div>
                                            
                                            <div>
                                                <span class="text-gray-600">Period:</span>
                                                <span class="font-semibold ml-1">
                                                    {{ $promotion->start_date->format('M d') }} - {{ $promotion->end_date->format('M d, Y') }}
                                                </span>
                                            </div>
                                            
                                            <div>
                                                <span class="text-gray-600">Usage:</span>
                                                <span class="font-semibold ml-1">
                                                    {{ $promotion->usage_count }}
                                                    @if($promotion->usage_limit)
                                                        / {{ $promotion->usage_limit }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2 ml-4">
                                        <a href="{{ route('staff.promotions.show', $promotion) }}" 
                                           class="text-blue-600 hover:text-blue-900 text-sm">
                                            View
                                        </a>
                                        <a href="{{ route('staff.promotions.edit', $promotion) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('staff.promotions.destroy', $promotion) }}" 
                                              class="inline" onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                No promotions found. <a href="{{ route('staff.promotions.create') }}" class="text-blue-600 hover:underline">Create one now</a>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $promotions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
