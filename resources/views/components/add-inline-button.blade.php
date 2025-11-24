@props([
    'target' => 'inline-create-modal',
    'label' => 'Add New',
    'icon' => true
])

<button
    type="button"
    x-data
    x-on:click="$dispatch('open-modal', '{{ $target }}')"
    data-modal-target="{{ $target }}"
    class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
    aria-label="{{ $label }}"
>
    @if($icon)
    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    @endif
    <span>{{ $label }}</span>
</button>
