/**
 * Image Management Component
 * Handles image upload, preview, reordering, and deletion
 */

class ImageManager {
    // Static Map to track ImageManager instances by element ID
    static instances = new Map();

    constructor(options = {}) {
        console.log('[ImageManager] Constructor called with options:', {
            hasFileInput: !!options.fileInput,
            hasUploadArea: !!options.uploadArea,
            productId: options.productId
        });
        
        // Get the file input element to determine the unique identifier
        const fileInput = options.fileInput || document.getElementById('images');
        const elementId = fileInput?.id;
        
        console.log('[ImageManager] Initialization attempt for element:', elementId);
        
        // Check if instance already exists for this element
        if (elementId && ImageManager.instances.has(elementId)) {
            console.log(`[ImageManager] Returning existing instance for element ${elementId} (singleton pattern)`);
            return ImageManager.instances.get(elementId);
        }
        
        // Initialize instance properties
        this.uploadArea = options.uploadArea || document.getElementById('image-upload-area');
        this.fileInput = fileInput;
        this.previewArea = options.previewArea || document.getElementById('image-preview-area');
        this.existingImagesArea = options.existingImagesArea || document.getElementById('existing-images');
        this.maxFileSize = options.maxFileSize || 5 * 1024 * 1024; // 5MB
        this.allowedTypes = options.allowedTypes || ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        this.maxFiles = options.maxFiles || 10;
        this.productId = options.productId || null;
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.newImages = [];
        this.imagesToDelete = [];
        // Track processed files to prevent duplicate processing
        this.processedFiles = new Set();
        
        // Store instance in the static Map
        if (elementId) {
            ImageManager.instances.set(elementId, this);
            console.log(`[ImageManager] Created new instance for element ${elementId}`);
            console.log(`[ImageManager] Total instances: ${ImageManager.instances.size}`);
        }
        
        this.init();
    }

    init() {
        console.log('[ImageManager] Initializing...');
        
        if (!this.uploadArea || !this.fileInput) {
            console.warn('[ImageManager] Required elements not found', {
                uploadArea: this.uploadArea,
                fileInput: this.fileInput
            });
            return;
        }

        console.log('[ImageManager] Initialized successfully', {
            uploadArea: this.uploadArea.id,
            fileInput: this.fileInput.id,
            maxFiles: this.maxFiles,
            productId: this.productId,
            hasExistingImages: !!this.existingImagesArea
        });

        this.setupEventListeners();
        this.setupDragAndDrop();
        
        // Initialize sortable for existing images if available
        if (this.existingImagesArea) {
            console.log('[ImageManager] Initializing sortable for existing images');
            this.initializeSortable(this.existingImagesArea);
        }
    }

    setupEventListeners() {
        console.log('[ImageManager] Setting up event listeners');
        
        // File input change
        this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        console.log('[ImageManager] File input change listener attached');
        
        // Click on upload area to trigger file input
        this.uploadArea.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('[ImageManager] Upload area clicked');
            this.fileInput.click();
        });
        
        // Also handle clicks on the label
        const label = this.uploadArea.querySelector('label');
        if (label) {
            label.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('[ImageManager] Label clicked');
                this.fileInput.click();
            });
        }
    }

    setupDragAndDrop() {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.uploadArea.addEventListener(eventName, this.preventDefaults, false);
            document.body.addEventListener(eventName, this.preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            this.uploadArea.addEventListener(eventName, () => this.highlight(), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.uploadArea.addEventListener(eventName, () => this.unhighlight(), false);
        });

        // Handle dropped files
        this.uploadArea.addEventListener('drop', (e) => this.handleDrop(e), false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight() {
        this.uploadArea.classList.add('border-blue-400', 'bg-blue-50');
    }

    unhighlight() {
        this.uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
    }

    handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        this.handleFiles(files);
    }

    handleFileSelect(e) {
        const files = e.target.files;
        console.log('[ImageManager] File selection event triggered');
        console.log('[ImageManager] Files selected:', files.length);
        
        // Filter out duplicate files
        const filesArray = Array.from(files);
        console.log('[ImageManager] Processing file selection:', {
            totalFiles: filesArray.length,
            processedFilesCount: this.processedFiles.size,
            fileNames: filesArray.map(f => f.name)
        });
        
        const newFiles = filesArray.filter(file => {
            const signature = this.generateFileSignature(file);
            
            // Check if file has already been processed
            if (this.processedFiles.has(signature)) {
                console.log(`[ImageManager] Duplicate file detected, skipping: ${file.name} (signature: ${signature})`);
                return false;
            }
            
            // Mark file as processed
            this.processedFiles.add(signature);
            console.log(`[ImageManager] New file added to processing queue: ${file.name} (signature: ${signature})`);
            return true;
        });
        
        console.log(`[ImageManager] File processing summary: ${newFiles.length} new files, ${filesArray.length - newFiles.length} duplicates skipped`);
        
        // Only process new files
        if (newFiles.length > 0) {
            this.handleFiles(newFiles);
        } else {
            console.log('[ImageManager] No new files to process (all were duplicates)');
        }
    }

    /**
     * Generate a unique signature for a file based on name, size, and lastModified timestamp
     * @param {File} file - The file to generate a signature for
     * @returns {string} - Unique file signature
     */
    generateFileSignature(file) {
        return `${file.name}-${file.size}-${file.lastModified}`;
    }

    handleFiles(files) {
        console.log('[ImageManager] handleFiles called with', files.length, 'files');
        
        // Validate file count
        const currentCount = this.newImages.length + (this.existingImagesArea?.children.length || 0);
        console.log('[ImageManager] Current image count:', {
            newImages: this.newImages.length,
            existingImages: this.existingImagesArea?.children.length || 0,
            total: currentCount,
            maxAllowed: this.maxFiles
        });
        
        if (currentCount + files.length > this.maxFiles) {
            console.warn('[ImageManager] File count limit exceeded');
            this.showError(`Maximum ${this.maxFiles} images allowed`);
            return;
        }

        // Process each file
        Array.from(files).forEach(file => {
            console.log('[ImageManager] Validating file:', file.name);
            if (this.validateFile(file)) {
                console.log('[ImageManager] File validation passed, creating preview:', file.name);
                this.previewFile(file);
            } else {
                console.log('[ImageManager] File validation failed:', file.name);
            }
        });
    }

    validateFile(file) {
        // Check file type
        if (!this.allowedTypes.includes(file.type)) {
            this.showError(`${file.name}: Invalid file type. Only JPEG, PNG, and WebP are allowed.`);
            return false;
        }

        // Check file size
        if (file.size > this.maxFileSize) {
            this.showError(`${file.name}: File size exceeds 5MB limit.`);
            return false;
        }

        return true;
    }

    previewFile(file) {
        console.log('[ImageManager] Creating preview for file:', file.name);
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const imageData = {
                file: file,
                dataUrl: e.target.result,
                id: `new-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
            };
            
            console.log('[ImageManager] File loaded, adding to newImages array:', {
                fileName: file.name,
                imageId: imageData.id,
                newImagesCount: this.newImages.length + 1
            });
            
            this.newImages.push(imageData);
            this.renderPreview(imageData);
        };
        
        reader.readAsDataURL(file);
    }

    renderPreview(imageData) {
        const div = document.createElement('div');
        div.className = 'relative group image-item';
        div.dataset.imageId = imageData.id;
        div.draggable = true;
        
        div.innerHTML = `
            <div class="relative">
                <img src="${imageData.dataUrl}" 
                     alt="Preview" 
                     class="w-full h-32 object-cover rounded-lg border-2 border-gray-200">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center space-x-2">
                    <button type="button" 
                            class="delete-image-btn p-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                            data-image-id="${imageData.id}"
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
            </div>
            <div class="mt-2">
                <label class="flex items-center text-sm cursor-pointer">
                    <input type="radio" 
                           name="primary_image_new" 
                           value="${imageData.id}" 
                           class="mr-2 primary-image-radio"
                           ${this.newImages.length === 1 && !this.hasPrimaryImage() ? 'checked' : ''}>
                    <span class="text-gray-700">Primary Image</span>
                </label>
            </div>
        `;
        
        // Add event listeners
        const deleteBtn = div.querySelector('.delete-image-btn');
        deleteBtn.addEventListener('click', () => this.deleteNewImage(imageData.id));
        
        // Add drag event listeners
        this.setupDragEvents(div);
        
        this.previewArea.appendChild(div);
        this.initializeSortable(this.previewArea);
    }

    setupDragEvents(element) {
        element.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', element.innerHTML);
            element.classList.add('opacity-50');
        });

        element.addEventListener('dragend', (e) => {
            element.classList.remove('opacity-50');
        });

        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = this.getDragAfterElement(element.parentElement, e.clientY);
            const draggable = document.querySelector('.opacity-50');
            
            if (afterElement == null) {
                element.parentElement.appendChild(draggable);
            } else {
                element.parentElement.insertBefore(draggable, afterElement);
            }
        });
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.image-item:not(.opacity-50)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    initializeSortable(container) {
        if (!container) return;
        
        // Simple sortable implementation
        const items = container.querySelectorAll('.image-item');
        items.forEach(item => {
            if (!item.draggable) {
                item.draggable = true;
                this.setupDragEvents(item);
            }
        });
    }

    deleteNewImage(imageId) {
        if (!confirm('Are you sure you want to remove this image?')) {
            return;
        }

        // Remove from array
        this.newImages = this.newImages.filter(img => img.id !== imageId);
        
        // Remove from DOM
        const element = this.previewArea.querySelector(`[data-image-id="${imageId}"]`);
        if (element) {
            element.remove();
        }

        // If this was the primary image, set first image as primary
        if (!this.hasPrimaryImage() && this.newImages.length > 0) {
            const firstRadio = this.previewArea.querySelector('.primary-image-radio');
            if (firstRadio) {
                firstRadio.checked = true;
            }
        }
    }

    deleteExistingImage(imageId) {
        if (!confirm('Are you sure you want to remove this image?')) {
            return;
        }

        // If productId exists, make AJAX call to delete
        if (this.productId) {
            this.deleteImageAjax(imageId);
        } else {
            // Mark for deletion on form submit
            this.imagesToDelete.push(imageId);
            
            // Hide the image element
            const element = this.existingImagesArea?.querySelector(`[data-image-id="${imageId}"]`);
            if (element) {
                element.style.display = 'none';
                
                // Remove keep_images input
                const keepInput = element.querySelector('input[name="keep_images[]"]');
                if (keepInput) {
                    keepInput.remove();
                }
            }
        }
    }

    async deleteImageAjax(imageId) {
        try {
            const response = await fetch(`/staff/products/images/${imageId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Remove from DOM
                const element = this.existingImagesArea?.querySelector(`[data-image-id="${imageId}"]`);
                if (element) {
                    element.remove();
                }
                this.showSuccess('Image deleted successfully');
            } else {
                this.showError(data.message || 'Failed to delete image');
            }
        } catch (error) {
            console.error('Delete image error:', error);
            this.showError('Failed to delete image');
        }
    }

    async setPrimaryImage(imageId, isExisting = false) {
        if (this.productId && isExisting) {
            // Make AJAX call to set primary image
            try {
                const response = await fetch(`/staff/products/images/${imageId}/set-primary`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Primary image updated');
                } else {
                    this.showError(data.message || 'Failed to set primary image');
                }
            } catch (error) {
                console.error('Set primary image error:', error);
                this.showError('Failed to set primary image');
            }
        }
    }

    async updateImageOrder() {
        if (!this.productId || !this.existingImagesArea) return;

        const imageOrder = [];
        const images = this.existingImagesArea.querySelectorAll('.image-item:not([style*="display: none"])');
        
        images.forEach((img, index) => {
            const imageId = img.dataset.imageId;
            if (imageId && !imageId.startsWith('new-')) {
                imageOrder.push({ id: imageId, order: index + 1 });
            }
        });

        try {
            const response = await fetch(`/staff/products/${this.productId}/images/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order: imageOrder })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Image order updated');
            } else {
                this.showError(data.message || 'Failed to update image order');
            }
        } catch (error) {
            console.error('Update image order error:', error);
            this.showError('Failed to update image order');
        }
    }

    hasPrimaryImage() {
        // Check existing images
        if (this.existingImagesArea) {
            const existingPrimary = this.existingImagesArea.querySelector('input[name="primary_image_existing"]:checked');
            if (existingPrimary) return true;
        }
        
        // Check new images
        const newPrimary = this.previewArea?.querySelector('input[name="primary_image_new"]:checked');
        return !!newPrimary;
    }

    getNewImagesData() {
        return this.newImages;
    }

    getImagesToDelete() {
        return this.imagesToDelete;
    }

    showError(message) {
        // Create or update error message element
        let errorEl = document.getElementById('image-manager-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.id = 'image-manager-error';
            errorEl.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50';
            document.body.appendChild(errorEl);
        }
        
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        
        setTimeout(() => {
            errorEl.style.display = 'none';
        }, 5000);
    }

    showSuccess(message) {
        // Create or update success message element
        let successEl = document.getElementById('image-manager-success');
        if (!successEl) {
            successEl = document.createElement('div');
            successEl.id = 'image-manager-success';
            successEl.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50';
            document.body.appendChild(successEl);
        }
        
        successEl.textContent = message;
        successEl.style.display = 'block';
        
        setTimeout(() => {
            successEl.style.display = 'none';
        }, 3000);
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageManager;
}

// Make available globally
window.ImageManager = ImageManager;
