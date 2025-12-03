@props([
    'productId',
    'productName',
    'stockQuantity',
    'deleteRoute'
])

<div
    x-data="{ show: false }"
    x-on:open-delete-modal.window="if ($event.detail.productId === {{ $productId }}) { show = true }"
    x-on:close-delete-modal.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    aria-describedby="modal-description"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 transform transition-all"
    >
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <!-- Modal Dialog -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto"
    >
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <!-- Warning Icon -->
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $stockQuantity > 0 ? 'bg-red-100' : 'bg-yellow-100' }} sm:mx-0 sm:h-10 sm:w-10">
                    @if($stockQuantity > 0)
                        <!-- Warning icon with exclamation for products with stock -->
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    @else
                        <!-- Standard warning icon for products without stock -->
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    @endif
                </div>

                <!-- Modal Content -->
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Delete Product
                    </h3>
                    <div class="mt-2" id="modal-description">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete 
                            <span class="font-semibold text-gray-900">{{ $productName }}</span>?
                        </p>
                        
                        @if($stockQuantity > 0)
                            <!-- Warning message for products with stock -->
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800">
                                            Warning: Product has stock
                                        </h4>
                                        <p class="mt-1 text-sm text-red-700">
                                            This product currently has <span class="font-semibold">{{ $stockQuantity }} units</span> in stock across all locations. 
                                            Deleting this product will permanently remove all stock records and cannot be undone.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Standard confirmation message for products without stock -->
                            <p class="mt-3 text-sm text-gray-500">
                                This action cannot be undone. This will permanently delete the product and all associated data.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <!-- Delete Button with destructive styling -->
            <form method="POST" action="{{ $deleteRoute }}" class="inline">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition ease-in-out duration-150"
                    aria-label="Confirm delete product"
                >
                    Delete Product
                </button>
            </form>

            <!-- Cancel Button -->
            <button
                type="button"
                x-on:click="show = false"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm transition ease-in-out duration-150"
                aria-label="Cancel delete"
            >
                Cancel
            </button>
        </div>
    </div>
</div>
