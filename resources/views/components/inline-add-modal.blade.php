@props([
    'name' => 'inline-modal',
    'title' => 'Add New Item',
    'route' => '',
    'inputLabel' => 'Name',
    'inputName' => 'name',
    'inputPlaceholder' => 'Enter name',
    'refreshEvent' => 'refresh-dropdown'
])

<div
    x-data="inlineAddModal('{{ $name }}', '{{ $route }}', '{{ $refreshEvent }}')"
    x-show="show"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:keydown.escape.window="if (show) closeModal()"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        x-on:click="closeModal()"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full"
            x-on:click.stop
        >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                    <button
                        type="button"
                        x-on:click="closeModal()"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <form x-on:submit.prevent="submitForm()">
                <div class="px-6 py-4">
                    <!-- Error Message -->
                    <div x-show="error" x-transition class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-600" x-text="error"></p>
                    </div>

                    <!-- Input Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $inputLabel }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="formData.{{ $inputName }}"
                            x-ref="nameInput"
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="{{ $inputPlaceholder }}"
                        >
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <button
                        type="button"
                        x-on:click="closeModal()"
                        :disabled="loading"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <span x-show="!loading">Add</span>
                        <span x-show="loading">Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function inlineAddModal(name, route, refreshEvent) {
    return {
        show: false,
        loading: false,
        error: '',
        formData: {
            name: ''
        },

        closeModal() {
            this.show = false;
            this.error = '';
            this.formData.name = '';
        },

        async submitForm() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Success - dispatch refresh event
                    window.dispatchEvent(new CustomEvent(refreshEvent, { detail: data.data }));
                    
                    // Show success message
                    this.showToast('success', data.message || 'Item added successfully');
                    
                    // Close modal
                    this.closeModal();
                } else {
                    // Handle error
                    this.error = data.message || 'Failed to add item';
                }
            } catch (error) {
                console.error('Error:', error);
                this.error = 'An unexpected error occurred. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-md shadow-lg transition-opacity duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }
    };
}
</script>
