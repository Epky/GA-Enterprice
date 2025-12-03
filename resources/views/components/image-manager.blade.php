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
                            <img src="{{ $image->full_url }}" 
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
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors" 
             id="image-upload-area">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" 
                      stroke-width="2" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <span class="mt-2 block text-sm font-medium text-gray-900">
                    Click the button below to upload images
                </span>
                <span class="mt-1 block text-sm text-gray-500">
                    PNG, JPG, WebP up to 5MB each (max {{ $maxFiles }} images)
                </span>
            </div>
        </div>
        
        <!-- Browse Button - OUTSIDE upload area -->
        <div class="mt-4 text-center">
            <button type="button" 
                    id="browse-files-btn-{{ str_replace('-', '_', $name) }}"
                    class="browse-files-btn inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg"
                    data-target-input="{{ $name }}">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Browse Files
            </button>
        </div>
        
        <input type="file" 
               name="{{ $name }}[]" 
               id="{{ $name }}"
               multiple
               accept="image/jpeg,image/jpg,image/png,image/webp"
               {{ $required ? 'required' : '' }}
               class="hidden"
               onchange="console.log('File input changed:', this.files.length, 'files')">
        
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

<script>
    (function() {
        // Unique initialization flag for this instance
        const initFlagName = 'imageManagerInit_{{ str_replace('-', '_', $name) }}';
        
        // Wait for both DOM and ImageManager class to be ready
        function initImageManager_{{ str_replace('-', '_', $name) }}() {
            // Check if already initialized
            if (window[initFlagName]) {
                console.log('ImageManager already initialized for {{ $name }}');
                return;
            }
            
            if (typeof ImageManager === 'undefined') {
                console.log('ImageManager not loaded yet, retrying...');
                setTimeout(initImageManager_{{ str_replace('-', '_', $name) }}, 100);
                return;
            }

            console.log('Initializing ImageManager for {{ $name }}');
            
            // Get elements
            const uploadArea = document.getElementById('image-upload-area');
            const fileInput = document.getElementById('{{ $name }}');
            const previewArea = document.getElementById('image-preview-area');
            const existingImagesArea = document.getElementById('existing-images');
            
            if (!uploadArea || !fileInput) {
                console.error('Required elements not found:', { uploadArea, fileInput });
                setTimeout(initImageManager_{{ str_replace('-', '_', $name) }}, 200);
                return;
            }
            
            // Mark as initialized BEFORE creating instance
            window[initFlagName] = true;
            
            // Initialize image manager
            const imageManagerInstance = new ImageManager({
                uploadArea: uploadArea,
                fileInput: fileInput,
                previewArea: previewArea,
                existingImagesArea: existingImagesArea,
                maxFiles: {{ $maxFiles }},
                productId: {{ $product?->id ?? 'null' }},
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
            });

            // Set as global imageManager
            window.imageManager = imageManagerInstance;
            window.imageManager_{{ str_replace('-', '_', $name) }} = imageManagerInstance;

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
            if (existingImagesArea) {
                existingImagesArea.addEventListener('dragend', function() {
                    // Delay to allow DOM to update
                    setTimeout(() => {
                        imageManagerInstance.updateImageOrder();
                    }, 100);
                });
            }
            
            // Setup Browse Files button with proper event handling
            const browseBtn = document.getElementById('browse-files-btn-{{ str_replace('-', '_', $name) }}');
            if (browseBtn) {
                browseBtn.addEventListener('click', function(e) {
                    // Stop all event propagation to prevent conflicts with other handlers
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    console.log('Browse Files button clicked - opening file browser');
                    
                    // Trigger the file input
                    const targetInput = document.getElementById(this.dataset.targetInput);
                    if (targetInput) {
                        targetInput.click();
                    } else {
                        console.error('Target file input not found:', this.dataset.targetInput);
                    }
                }, true); // Use capture phase to ensure this runs first
            }
            
            console.log('ImageManager initialized successfully for {{ $name }}');
        }

        // Single initialization approach with { once: true } option
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initImageManager_{{ str_replace('-', '_', $name) }}, { once: true });
        } else {
            // DOM already loaded, initialize immediately
            initImageManager_{{ str_replace('-', '_', $name) }}();
        }
    })();
</script>
