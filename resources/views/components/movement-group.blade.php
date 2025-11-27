{{--
    Movement Group Component
    
    Displays a grouped inventory movement with optional related system movements.
    
    Props:
    - group: Either a single InventoryMovement model or a grouped structure with:
        - 'primary': The main business movement (InventoryMovement)
        - 'related': Collection of related system movements
        - 'transaction_ref': Optional transaction reference ID
    - showSystemMovements: Boolean to show/hide related system movements (default: false)
    
    Requirements: 2.2, 2.3, 2.5
--}}
@props(['group', 'showSystemMovements' => false])

@php
    // Determine if this is a grouped movement or a single movement
    $isGrouped = isset($group['primary']) && isset($group['related']);
    $primaryMovement = $isGrouped ? $group['primary'] : $group;
    $relatedMovements = $isGrouped ? $group['related'] : collect();
    $transactionRef = $isGrouped ? ($group['transaction_ref'] ?? null) : null;
    
    // Get transaction reference from primary movement if not provided
    if (!$transactionRef && $primaryMovement->transaction_reference) {
        $transactionRef = $primaryMovement->transaction_reference['id'];
    }
@endphp

<div class="movement-group">
    {{-- Primary Movement Row --}}
    <tr class="hover:bg-gray-50 {{ $isGrouped && $relatedMovements->isNotEmpty() ? 'border-l-4 border-blue-400' : '' }}">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            <div class="flex items-center">
                @if($isGrouped && $relatedMovements->isNotEmpty())
                    <svg class="h-4 w-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                @endif
                <div>
                    <div>{{ $primaryMovement->created_at->format('M d, Y') }}</div>
                    <div class="text-xs text-gray-500">{{ $primaryMovement->created_at->format('h:i A') }}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900">{{ $primaryMovement->display_name }}</div>
            @if($primaryMovement->product)
            <div class="text-xs text-gray-500">SKU: {{ $primaryMovement->product->sku }}</div>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                @if($primaryMovement->movement_direction === 'in') bg-green-100 text-green-800
                @elseif($primaryMovement->movement_direction === 'out') bg-red-100 text-red-800
                @else bg-blue-100 text-blue-800
                @endif">
                {{ $primaryMovement->movement_type_label }}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium 
                @if($primaryMovement->quantity > 0) text-green-600
                @else text-red-600
                @endif">
                @if($primaryMovement->quantity > 0)+@endif{{ $primaryMovement->quantity }}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $primaryMovement->location_display }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">{{ $primaryMovement->performedBy?->name ?? 'System' }}</div>
            <div class="text-xs text-gray-500">{{ $primaryMovement->created_at->diffForHumans() }}</div>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm text-gray-900 max-w-xs">
                @if($primaryMovement->reason)
                <div class="mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $primaryMovement->reason }}
                    </span>
                </div>
                @endif
                
                @if($transactionRef)
                <div class="mb-1">
                    @php
                        // Find the order by order_number (transaction reference)
                        $order = \App\Models\Order::where('order_number', $transactionRef)->first();
                    @endphp
                    @if($order)
                        <a href="{{ route('staff.walk-in-transaction.show', ['order' => $order->id]) }}" 
                           class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            {{ $transactionRef }}
                        </a>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            {{ $transactionRef }}
                        </span>
                    @endif
                </div>
                @endif
                
                @if($primaryMovement->clean_notes)
                <div class="text-xs text-gray-600">{{ $primaryMovement->clean_notes }}</div>
                @elseif(!$primaryMovement->reason && !$transactionRef)
                <span class="text-gray-400 text-xs italic">No notes</span>
                @endif
            </div>
        </td>
    </tr>

    {{-- Related System Movements (nested) --}}
    @if($showSystemMovements && $isGrouped && $relatedMovements->isNotEmpty())
        @foreach($relatedMovements as $relatedMovement)
        <tr class="bg-gray-50 hover:bg-gray-100 border-l-4 border-gray-300">
            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                <div class="flex items-center pl-6">
                    <svg class="h-3 w-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <div>
                        <div class="text-xs">{{ $relatedMovement->created_at->format('M d, Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $relatedMovement->created_at->format('h:i A') }}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-3">
                <div class="text-xs text-gray-600 pl-6">{{ $relatedMovement->display_name }}</div>
            </td>
            <td class="px-6 py-3 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">
                    {{ ucfirst($relatedMovement->movement_type) }}
                </span>
            </td>
            <td class="px-6 py-3 whitespace-nowrap">
                <div class="text-xs text-gray-600">
                    @if($relatedMovement->quantity > 0)+@endif{{ $relatedMovement->quantity }}
                </div>
            </td>
            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500">
                {{ $relatedMovement->location_display }}
            </td>
            <td class="px-6 py-3 whitespace-nowrap">
                <div class="text-xs text-gray-600">{{ $relatedMovement->performedBy?->name ?? 'System' }}</div>
            </td>
            <td class="px-6 py-3">
                <div class="text-xs text-gray-500 max-w-xs">
                    @if($relatedMovement->clean_notes)
                        {{ Str::limit($relatedMovement->clean_notes, 50) }}
                    @else
                        <span class="text-gray-400 italic">System operation</span>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    @endif
</div>
