@props([
    'product' => null,
    'existingImages' => [],
    'name' => 'images',
    'maxFiles' => 10,
    'required' => false
])

<div class="image-manager-component" data-product-id="{{ $product?->id }}">
    <!-- Existing Images Section -->
    @if($product && count($existingImages) > 0)
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-700 mb-3">Current Images</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="existing-images">
                @foreach($existingImages as $image)
                    <div class="relative group image-item existing-image" 
                         data-image-id="{{ $image->id }}"
                         draggable="true">
                        <div class="relative">
                            <img src="{{ asset($image->full_url) }}" 
                                 alt="{{ $image->alt_text }}" 
                                 class="w-full h-32 object-cover rounded-lg border-2 border-gray-200">
                            
                            <!-- Hover overlay with actions -->
                            <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center space-x-2">
                                <button type="button" 
                                        class="delete-existing-image-btn p-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                                        data-image-id="{{ $image->id }}"
                                        title="Delete image">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <button type="button" 
                                        class="move-handle p-2 bg-gray-600 text-white rounded-full hover:bg-gray-700 transition-colors cursor-move"
                                        title="Drag to reorder">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Primary image badge -->
                            @if($image->is_primary)
                                <div class="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">
                                    Primary
                                </div>
                            @endif
                        </div>
                        
                        <!-- Primary image radio -->
                        <div class="mt-2">
                            <label class="flex items-center text-sm cursor-pointer">
                                <input type="radio" 
                                       name="primary_image_existing" 
                                       value="{{ $image->id }}" 
                                       class="mr-2 primary-image-radio"
                                       {{ $image->is_primary ? 'checked' : '' }}
                                       onchange="window.imageManager?.setPrimaryImage({{ $image->id }}, true)">
                                <span class="text-gray-700">Primary Image</span>
                            </label>
                        </div>
                        
                        <!-- Hidden input to keep this image -->
                        <input type="hidden" name="keep_images[]" value="{{ $image->id }}">
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Upload New Images Section -->
    <div class="mb-6">
        <h4 class="text-md font-medium text-gray-700 mb-3">
            {{ $product ? 'Add New Images' : 'Upload Images' }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </h4>
        
        <!-- Upload Area -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors cursor-pointer upload-trigger" 
             id="image-upload-area">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" 
                      stroke-width="2" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <label for="{{ $name }}" class="cursor-pointer">
                    <span class="mt-2 block text-sm font-medium text-gray-900">
                        Click to upload images or drag and drop
                    </span>
                    <span class="mt-1 block text-sm text-gray-500">
                        PNG, JPG, WebP up to 5MB each (max {{ $maxFiles }} images)
                    </span>
                </label>
                <input type="file" 
                       name="{{ $name }}[]" 
                       id="{{ $name }}"
                       multiple
                       accept="image/jpeg,image/jpg,image/png,image/webp"
                       {{ $required ? 'required' : '' }}
                       class="hidden">
            </div>
        </div>
        
        @error($name)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error($name . '.*')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- New Image Preview Area -->
    <div id="image-preview-area" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- New image previews will be added here dynamically -->
    </div>
</div>

@push('scripts')
<script>
    (function() {
        // Wait for both DOM and ImageManager class to be ready
        function initImageManager() {
            if (typeof ImageManager === 'undefined') {
                console.log('ImageManager not loaded yet, retrying...');
                setTimeout(initImageManager, 100);
                return;
            }

            console.log('Initializing ImageManager for {{ $name }}');
            
            // Initialize image manager
            const imageManagerInstance = new ImageManager({
                uploadArea: document.getElementById('image-upload-area'),
                fileInput: document.getElementById('{{ $name }}'),
                previewArea: document.getElementById('image-preview-area'),
                existingImagesArea: document.getElementById('existing-images'),
                maxFiles: {{ $maxFiles }},
                productId: {{ $product?->id ?? 'null' }},
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
            });

            // Set as global imageManager
            window.imageManager = imageManagerInstance;

            // Setup delete buttons for existing images
            document.querySelectorAll('.delete-existing-image-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const imageId = this.dataset.imageId;
                    imageManagerInstance.deleteExistingImage(imageId);
                });
            });

            // Setup drag end event to update order
            const existingImagesArea = document.getElementById('existing-images');
            if (existingImagesArea) {
                existingImagesArea.addEventListener('dragend', function() {
                    // Delay to allow DOM to update
                    setTimeout(() => {
                        imageManagerInstance.updateImageOrder();
                    }, 100);
                });
            }
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initImageManager);
        } else {
            initImageManager();
        }
    })();
</script>
@endpush
