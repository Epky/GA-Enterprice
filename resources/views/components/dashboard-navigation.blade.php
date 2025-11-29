@props(['current' => 'overview', 'period' => null])

@php
    // Build query string for period persistence
    $queryParams = [];
    if ($period) {
        $queryParams['period'] = $period;
        if ($period === 'custom') {
            if (request('start_date')) {
                $queryParams['start_date'] = request('start_date');
            }
            if (request('end_date')) {
                $queryParams['end_date'] = request('end_date');
            }
        }
    }
    $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
    
    // Define navigation items
    $navItems = [
        [
            'name' => 'Overview',
            'route' => 'admin.dashboard',
            'key' => 'overview',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'
        ],
        [
            'name' => 'Sales & Revenue',
            'route' => 'admin.dashboard.sales',
            'key' => 'sales',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        [
            'name' => 'Customers & Channels',
            'route' => 'admin.dashboard.customers',
            'key' => 'customers',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />'
        ],
        [
            'name' => 'Inventory Insights',
            'route' => 'admin.dashboard.inventory',
            'key' => 'inventory',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />'
        ]
    ];
@endphp

<!-- Desktop Navigation -->
<nav class="bg-white shadow-sm mb-6 rounded-xl overflow-hidden hidden md:block" role="navigation" aria-label="Dashboard navigation">
    <div class="flex">
        @foreach($navItems as $item)
            @php
                $isActive = $current === $item['key'];
                $activeClasses = $isActive 
                    ? 'bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white' 
                    : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:via-purple-50 hover:to-indigo-50 hover:text-purple-700';
            @endphp
            
            <a href="{{ route($item['route']) . $queryString }}" 
               class="flex-1 flex items-center justify-center px-6 py-4 text-sm font-semibold transition-all duration-300 {{ $activeClasses }} {{ $isActive ? '' : 'hover:scale-105' }}"
               aria-current="{{ $isActive ? 'page' : 'false' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    </div>
</nav>

<!-- Mobile Navigation -->
<nav class="bg-white shadow-sm mb-6 rounded-xl overflow-hidden md:hidden" role="navigation" aria-label="Dashboard navigation" x-data="{ open: false }">
    <!-- Mobile Menu Button -->
    <button @click="open = !open" 
            type="button"
            class="w-full flex items-center justify-between px-4 py-4 text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:via-purple-50 hover:to-indigo-50 transition-all duration-200"
            aria-expanded="false"
            aria-controls="mobile-dashboard-menu">
        <div class="flex items-center">
            @php
                $currentItem = collect($navItems)->firstWhere('key', $current);
            @endphp
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                {!! $currentItem['icon'] !!}
            </svg>
            <span class="font-semibold">{{ $currentItem['name'] }}</span>
        </div>
        <svg class="w-5 h-5 transition-transform duration-200" 
             :class="{ 'rotate-180': open }"
             fill="none" 
             stroke="currentColor" 
             viewBox="0 0 24 24"
             aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    
    <!-- Mobile Menu Items -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         id="mobile-dashboard-menu"
         class="border-t border-gray-200">
        @foreach($navItems as $item)
            @php
                $isActive = $current === $item['key'];
                $activeClasses = $isActive 
                    ? 'bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white' 
                    : 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:via-purple-50 hover:to-indigo-50 hover:text-purple-700';
            @endphp
            
            <a href="{{ route($item['route']) . $queryString }}" 
               class="flex items-center px-4 py-3 text-sm font-medium transition-all duration-200 {{ $activeClasses }}"
               aria-current="{{ $isActive ? 'page' : 'false' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['name'] }}</span>
                @if($isActive)
                    <svg class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</nav>
