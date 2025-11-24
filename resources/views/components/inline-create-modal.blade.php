@props([
    'type' => 'category', // 'category' or 'brand'
    'parentCategories' => null,
    'modalId' => 'inline-create-modal'
])

@php
$isCategory = $type === 'category';
$title = $isCategory ? 'Add New Category' : 'Add New Brand';
$description = $isCategory ? 'Create a new category for organizing products' : 'Create a new brand for products';
@endphp

<div
    x-data="{
        show: false,
        type: '{{ $type }}',
        previousActiveElement: null,
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            // Store the currently focused element to restore later
            previousActiveElement = document.activeElement;
            
            // Prevent body scroll
            document.body.classList.add('overflow-y-hidden');
            
            // Set focus to first focusable element after modal opens
            setTimeout(() => {
                const first = firstFocusable();
                if (first) {
                    first.focus();
                }
            }, 100);
        } else {
            // Restore body scroll
            document.body.classList.remove('overflow-y-hidden');
            
            // Restore focus to the element that opened the modal
            setTimeout(() => {
                if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                    previousActiveElement.focus();
                }
                previousActiveElement = null;
            }, 100);
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $modalId }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $modalId }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="if (show) { show = false; $event.stopPropagation(); }"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: none;"
    id="{{ $modalId }}"
    data-modal-type="{{ $type }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    aria-describedby="{{ $modalId }}-description"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <!-- Modal Dialog -->
    <div
        x-show="show"
        class="mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <!-- Modal Header -->
        <div class="bg-white px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900" id="{{ $modalId }}-title">
                        {{ $title }}
                    </h3>
                    <p class="sr-only" id="{{ $modalId }}-description">{{ $description }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md p-1"
                    aria-label="Close {{ strtolower($title) }} dialog"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <form id="{{ $modalId }}-form" data-modal-id="{{ $modalId }}" data-type="{{ $type }}">
            <div class="bg-white px-6 py-4">
                @csrf

                <!-- Name Field -->
                <div class="mb-4">
                    <label for="{{ $modalId }}-name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $isCategory ? 'Category' : 'Brand' }} Name <span class="text-red-500" aria-label="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="{{ $modalId }}-name"
                        name="name"
                        required
                        aria-required="true"
                        aria-invalid="false"
                        aria-describedby="{{ $modalId }}-name-error"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter {{ strtolower($isCategory ? 'category' : 'brand') }} name"
                    >
                    <p class="mt-1 text-sm text-red-600 hidden" id="{{ $modalId }}-name-error" role="alert" aria-live="polite"></p>
                </div>

                <!-- Parent Category Field (Categories Only) -->
                @if($isCategory && $parentCategories)
                <div class="mb-4">
                    <label for="{{ $modalId }}-parent" class="block text-sm font-medium text-gray-700 mb-1">
                        Parent Category <span class="text-gray-500 text-xs">(Optional)</span>
                    </label>
                    <select
                        id="{{ $modalId }}-parent"
                        name="parent_id"
                        aria-describedby="{{ $modalId }}-parent-error"
                        aria-invalid="false"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">None (Top Level)</option>
                        @foreach($parentCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-red-600 hidden" id="{{ $modalId }}-parent-error" role="alert" aria-live="polite"></p>
                </div>
                @endif

                <!-- Description Field -->
                <div class="mb-4">
                    <label for="{{ $modalId }}-description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-gray-500 text-xs">(Optional)</span>
                    </label>
                    <textarea
                        id="{{ $modalId }}-description"
                        name="description"
                        rows="3"
                        aria-describedby="{{ $modalId }}-description-error"
                        aria-invalid="false"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter {{ strtolower($isCategory ? 'category' : 'brand') }} description"
                    ></textarea>
                    <p class="mt-1 text-sm text-red-600 hidden" id="{{ $modalId }}-description-error" role="alert" aria-live="polite"></p>
                </div>

                <!-- Active Checkbox -->
                <div class="mb-4">
                    <label for="{{ $modalId }}-active" class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            id="{{ $modalId }}-active"
                            name="is_active"
                            value="1"
                            checked
                            aria-describedby="{{ $modalId }}-active-help"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-offset-2"
                        >
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                    <p id="{{ $modalId }}-active-help" class="sr-only">Check to make this {{ $isCategory ? 'category' : 'brand' }} active and visible to users</p>
                </div>

                <!-- Error Message Container -->
                <div class="mb-4 hidden" id="{{ $modalId }}-error-container" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="bg-red-50 border border-red-200 rounded-md p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-800" id="{{ $modalId }}-error-message"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success Message Container -->
                <div class="mb-4 hidden" id="{{ $modalId }}-success-container" role="status" aria-live="polite" aria-atomic="true">
                    <div class="bg-green-50 border border-green-200 rounded-md p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-800" id="{{ $modalId }}-success-message"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button
                    type="button"
                    x-on:click="show = false"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    aria-label="Cancel and close dialog"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    id="{{ $modalId }}-submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    aria-label="Create {{ strtolower($isCategory ? 'category' : 'brand') }}"
                >
                    <span id="{{ $modalId }}-submit-text">Create</span>
                    <svg class="hidden animate-spin ml-2 h-4 w-4 text-white" id="{{ $modalId }}-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true" role="status">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="sr-only" x-show="$el.previousElementSibling.classList.contains('hidden') === false">Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>
