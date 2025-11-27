{{--
    Movement Notes Component
    
    Displays formatted inventory movement notes with extracted structured data.
    
    Props:
    - movement: InventoryMovement model instance
    - compact: Boolean for compact display (default: false)
    
    Requirements: 3.1, 3.2, 3.3, 3.4
--}}
@props(['movement', 'compact' => false])

@php
    $reason = $movement->reason;
    $transactionRef = $movement->transaction_reference;
    $cleanNotes = $movement->clean_notes;
    $hasContent = $reason || $transactionRef || $cleanNotes;
@endphp

<div {{ $attributes->merge(['class' => 'movement-notes']) }}>
    {{-- Reason Badge --}}
    @if($reason)
    <div class="{{ $compact ? 'mb-0.5' : 'mb-1' }}">
        <span class="inline-flex items-center px-2 py-0.5 rounded {{ $compact ? 'text-xs' : 'text-xs' }} font-medium bg-blue-100 text-blue-800">
            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ $reason }}
        </span>
    </div>
    @endif
    
    {{-- Transaction Reference Link --}}
    @if($transactionRef)
    <div class="{{ $compact ? 'mb-0.5' : 'mb-1' }}">
        @php
            // Find the order by order_number (transaction reference)
            $order = \App\Models\Order::where('order_number', $transactionRef['id'])->first();
        @endphp
        @if($order)
            <a href="{{ route('staff.walk-in-transaction.show', ['order' => $order->id]) }}" 
               class="inline-flex items-center px-2 py-0.5 rounded {{ $compact ? 'text-xs' : 'text-xs' }} font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                {{ $transactionRef['id'] }}
            </a>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded {{ $compact ? 'text-xs' : 'text-xs' }} font-medium bg-purple-100 text-purple-800">
                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                {{ $transactionRef['id'] }}
            </span>
        @endif
    </div>
    @endif
    
    {{-- Clean Notes Text --}}
    @if($cleanNotes)
    <div class="{{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600">
        {{ $cleanNotes }}
    </div>
    @endif
    
    {{-- Empty State Placeholder --}}
    @if(!$hasContent)
    <span class="text-gray-400 {{ $compact ? 'text-xs' : 'text-sm' }} italic">No notes</span>
    @endif
</div>
