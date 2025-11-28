@props(['title', 'value', 'icon' => null, 'color' => 'pink', 'change' => null, 'changeType' => null])

@php
    // Define gradient border colors based on color prop
    $borderColors = [
        'pink' => 'border-pink-500',
        'purple' => 'border-purple-500',
        'indigo' => 'border-indigo-500',
    ];
    
    // Define gradient icon backgrounds based on color prop
    $iconGradients = [
        'pink' => 'bg-gradient-to-br from-pink-400 to-pink-600',
        'purple' => 'bg-gradient-to-br from-purple-400 to-purple-600',
        'indigo' => 'bg-gradient-to-br from-indigo-400 to-indigo-600',
    ];
    
    // Define gradient text colors based on color prop
    $textGradients = [
        'pink' => 'bg-gradient-to-r from-pink-600 to-pink-700',
        'purple' => 'bg-gradient-to-r from-purple-600 to-purple-700',
        'indigo' => 'bg-gradient-to-r from-indigo-600 to-indigo-700',
    ];
    
    // Define change indicator colors based on color prop
    $changeColors = [
        'pink' => 'text-pink-600',
        'purple' => 'text-purple-600',
        'indigo' => 'text-indigo-600',
    ];
    
    $borderClass = $borderColors[$color] ?? $borderColors['pink'];
    $iconGradient = $iconGradients[$color] ?? $iconGradients['pink'];
    $textGradient = $textGradients[$color] ?? $textGradients['pink'];
    $changeColor = $changeColors[$color] ?? $changeColors['pink'];
@endphp

<div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 {{ $borderClass }} hover:shadow-2xl hover:scale-105 transition-all duration-300">
    <div class="p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600">{{ $title }}</p>
                <p class="mt-2 text-3xl font-bold {{ $textGradient }} bg-clip-text text-transparent">{{ $value }}</p>
                
                @if($change !== null && $changeType !== null)
                    <div class="mt-2 flex items-center text-sm">
                        @if($changeType === 'increase')
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            <span class="text-green-600 font-medium">{{ $change }}</span>
                        @elseif($changeType === 'decrease')
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                            <span class="text-red-600 font-medium">{{ $change }}</span>
                        @else
                            <span class="{{ $changeColor }} font-medium">{{ $change }}</span>
                        @endif
                        <span class="text-gray-500 ml-1">vs previous period</span>
                    </div>
                @endif
            </div>
            
            @if($icon)
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 {{ $iconGradient }} rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $icon !!}
                        </svg>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
