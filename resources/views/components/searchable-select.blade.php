@props([
    'name' => '',
    'label' => '',
    'items' => collect(),
    'selected' => null,
    'required' => false,
    'deleteRoute' => '',
    'refreshRoute' => '',
    'placeholder' => 'Select...'
])

@php
$selectedItem = $items->firstWhere('id', $selected);
$selectedText = $selectedItem ? $selectedItem->name : $placeholder;
@endphp

<div class="searchable-select-wrapper" data-component="searchable-select">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }} @if($required)<span class="text-red-500" aria-label="required">*</span>@endif
    </label>
    
    <div class="relative">
        <!-- Hidden input for form submission -->
        <input 
            type="hidden" 
            name="{{ $name }}" 
            id="{{ $name }}"
            value="{{ $selected }}"
            @if($required) required @endif
            data-delete-route-pattern="{{ $deleteRoute }}"
            data-refresh-route="{{ $refreshRoute ? route($refreshRoute) : '' }}"
        >
        
        <!-- Display button (trigger) -->
        <button 
            type="button" 
            class="searchable-select-trigger w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition ease-in-out duration-150"
            aria-haspopup="listbox"
            aria-expanded="false"
        >
            <span class="selected-text block truncate text-left {{ $selected ? 'text-gray-900' : 'text-gray-500' }}">
                {{ $selectedText }}
            </span>
            <svg class="h-5 w-5 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        
        <!-- Dropdown panel -->
        <div class="searchable-select-dropdown hidden absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-80 overflow-hidden" role="listbox">
            <!-- Search input -->
            <div class="search-box p-2 border-b border-gray-200 bg-gray-50">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        class="search-input w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search..."
                        autocomplete="off"
                    >
                </div>
            </div>
            
            <!-- Items list -->
            <ul class="items-list overflow-y-auto max-h-60">
                @forelse($items as $item)
                <li 
                    class="item flex items-center justify-between px-3 py-2 hover:bg-gray-100 cursor-pointer transition-colors duration-150 {{ $selected == $item->id ? 'bg-blue-50' : '' }}"
                    data-id="{{ $item->id }}" 
                    data-name="{{ $item->name }}"
                    role="option"
                    aria-selected="{{ $selected == $item->id ? 'true' : 'false' }}"
                >
                    <span class="item-name text-sm text-gray-900 flex-1">{{ $item->name }}</span>
                    <button 
                        type="button" 
                        class="delete-btn ml-2 p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-red-500"
                        data-id="{{ $item->id }}"
                        data-name="{{ $item->name }}"
                        aria-label="Delete {{ $item->name }}"
                        title="Delete {{ $item->name }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </li>
                @empty
                <li class="px-3 py-2 text-sm text-gray-500 text-center">
                    No items available
                </li>
                @endforelse
            </ul>
            
            <!-- No results message (hidden by default) -->
            <div class="no-results hidden px-3 py-4 text-sm text-gray-500 text-center">
                No results found
            </div>
        </div>
    </div>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
