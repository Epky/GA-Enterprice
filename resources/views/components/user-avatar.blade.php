@props(['user', 'size' => 'md'])

@php
    // Define size classes
    $sizeClasses = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-16 h-16 text-lg',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    // Get avatar URL or default
    $avatarUrl = $user->profile?->avatar_url ? Storage::url($user->profile->avatar_url) : null;
    
    // Get user name for alt text
    $userName = $user->profile ? "{$user->profile->first_name} {$user->profile->last_name}" : $user->name ?? $user->email;
    
    // Get initials for placeholder
    $initials = $user->getInitials();
@endphp

@if($avatarUrl)
    <img 
        src="{{ $avatarUrl }}" 
        alt="{{ $userName }}'s profile picture" 
        class="rounded-full object-cover {{ $sizeClass }} {{ $attributes->get('class') }}"
        {{ $attributes->except('class') }}
    >
@else
    <div 
        class="rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold {{ $sizeClass }} {{ $attributes->get('class') }}"
        {{ $attributes->except('class') }}
    >
        {{ $initials }}
    </div>
@endif
