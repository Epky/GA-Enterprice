<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Product: ') . $product->name }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('staff.products.show', $product) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Product
                </a>
                <button type="button" 
                        onclick="deleteProduct({{ $product->id }})" 
                        class="delete-product-btn inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-stock-quantity="{{ $product->total_stock }}">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Product
                </button>
                <a href="{{ route('staff.products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Products
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('staff.products.update', $product) }}" method="POST" enctype="multipart/form-data" id="product-form">
                @csrf
                @method('PUT')
                
                <!-- Progress Steps -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium step-indicator active" data-step="1">
                                        1
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-900">Basic Info</span>
                                </div>
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium step-indicator" data-step="2">
                                        2
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-500">Images</span>
                                </div>
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium step-indicator" data-step="3">
                                        3
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-500">Variants</span>
                                </div>
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium step-indicator" data-step="4">
                                        4
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-500">Review</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Basic Information -->
                <div class="form-step active" id="step-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Product Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Product Name -->
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="{{ old('name', $product->name) }}"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Enter product name">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- SKU -->
                                <div>
                                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                                    <input type="text" 
                                           name="sku" 
                                           id="sku"
                                           value="{{ old('sku', $product->sku) }}"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Enter unique SKU">
                                    @error('sku')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Price -->
                                <div>
                                    <label for="base_price" class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₱</span>
                                        </div>
                                        <input type="number" 
                                               name="base_price" 
                                               id="base_price"
                                               value="{{ old('base_price', $product->base_price) }}"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               placeholder="0.00">
                                    </div>
                                    @error('base_price')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Category -->
                                <div>
                                    <x-searchable-select
                                        name="category_id"
                                        label="Category *"
                                        :items="$categories"
                                        :selected="old('category_id', $product->category_id)"
                                        :required="true"
                                        deleteRoute="staff.categories.delete-inline"
                                        refreshRoute="staff.categories.active"
                                        placeholder="Select Category"
                                    />
                                    <div class="mt-2">
                                        <x-add-inline-button 
                                            target="category-modal" 
                                            label="Add New Category"
                                        />
                                    </div>
                                    @error('category_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Brand -->
                                <div>
                                    <x-searchable-select
                                        name="brand_id"
                                        label="Brand *"
                                        :items="$brands"
                                        :selected="old('brand_id', $product->brand_id)"
                                        :required="true"
                                        deleteRoute="staff.brands.delete-inline"
                                        refreshRoute="staff.brands.active"
                                        placeholder="Select Brand"
                                    />
                                    <div class="mt-2">
                                        <x-add-inline-button 
                                            target="brand-modal" 
                                            label="Add New Brand"
                                        />
                                    </div>
                                    @error('brand_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" 
                                              id="description"
                                              rows="4"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Enter product description">{{ old('description', $product->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="discontinued" {{ old('status', $product->status) == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                                        <option value="out_of_stock" {{ old('status', $product->status) == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                    </select>
                                    @error('status')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Featured -->
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="is_featured" 
                                               value="1"
                                               {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Mark as Featured Product</span>
                                    </label>
                                    @error('is_featured')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Images -->
                <div class="form-step" id="step-2" style="display: none;">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Product Images</h3>
                            
                            <x-image-manager 
                                :product="$product"
                                :existingImages="$product->images"
                                name="images"
                                :maxFiles="10"
                                :required="false"
                            />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Variants -->
                <div class="form-step" id="step-3" style="display: none;">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Product Variants</h3>
                                <button type="button" id="add-variant" class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Variant
                                </button>
                            </div>

                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Add variants if your product comes in different colors, sizes, or other variations. Leave empty if the product has no variants.</p>
                            </div>

                            <!-- Existing Variants -->
                            <div id="variants-container">
                                @foreach($product->variants as $index => $variant)
                                    <div class="variant-item border border-gray-200 rounded-lg p-4 mb-4" data-variant="{{ $variant->id }}">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-md font-medium text-gray-900">{{ $variant->variant_type }}: {{ $variant->variant_value }}</h4>
                                            <button type="button" onclick="removeVariant({{ $variant->id }})" class="text-red-600 hover:text-red-800">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <input type="hidden" name="variants[{{ $variant->id }}][id]" value="{{ $variant->id }}">
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Variant Type</label>
                                                <select name="variants[{{ $variant->id }}][variant_type]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="color" {{ $variant->variant_type == 'color' ? 'selected' : '' }}>Color</option>
                                                    <option value="size" {{ $variant->variant_type == 'size' ? 'selected' : '' }}>Size</option>
                                                    <option value="material" {{ $variant->variant_type == 'material' ? 'selected' : '' }}>Material</option>
                                                    <option value="style" {{ $variant->variant_type == 'style' ? 'selected' : '' }}>Style</option>
                                                    <option value="other" {{ $variant->variant_type == 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Variant Value</label>
                                                <input type="text" 
                                                       name="variants[{{ $variant->id }}][variant_value]" 
                                                       value="{{ $variant->variant_value }}"
                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                       placeholder="e.g., Red, Large, Cotton">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                                                <input type="text" 
                                                       name="variants[{{ $variant->id }}][sku]" 
                                                       value="{{ $variant->sku }}"
                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                       placeholder="Variant SKU">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Price Adjustment</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                                    </div>
                                                    <input type="number" 
                                                           name="variants[{{ $variant->id }}][price_adjustment]" 
                                                           value="{{ $variant->price_adjustment }}"
                                                           step="0.01"
                                                           class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                           placeholder="0.00">
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">Additional cost for this variant</p>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="variants[{{ $variant->id }}][is_active]" 
                                                           value="1"
                                                           {{ $variant->is_active ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="form-step" id="step-4" style="display: none;">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Review Product Changes</h3>
                            
                            <div id="review-content">
                                <!-- Review content will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between">
                            <button type="button" id="prev-step" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150" style="display: none;">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Previous
                            </button>
                            
                            <div class="flex space-x-3">
                                <button type="button" id="next-step" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Next
                                    <svg class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </button>
                                
                                <button type="submit" id="submit-product" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150" style="display: none;">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Product
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        let variantCounter = {{ $product->variants->count() }};
        let imagesToDelete = [];

        // Step navigation (same as create form)
        document.getElementById('next-step').addEventListener('click', function() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        });

        document.getElementById('prev-step').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(stepEl => {
                stepEl.style.display = 'none';
            });

            // Show current step
            document.getElementById(`step-${step}`).style.display = 'block';

            // Update step indicators
            document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
                const stepNumber = index + 1;
                if (stepNumber <= step) {
                    indicator.classList.add('active');
                    indicator.classList.remove('bg-gray-300', 'text-gray-600');
                    indicator.classList.add('bg-blue-600', 'text-white');
                    indicator.nextElementSibling.classList.remove('text-gray-500');
                    indicator.nextElementSibling.classList.add('text-gray-900');
                } else {
                    indicator.classList.remove('active');
                    indicator.classList.remove('bg-blue-600', 'text-white');
                    indicator.classList.add('bg-gray-300', 'text-gray-600');
                    indicator.nextElementSibling.classList.remove('text-gray-900');
                    indicator.nextElementSibling.classList.add('text-gray-500');
                }
            });

            // Update navigation buttons
            document.getElementById('prev-step').style.display = step > 1 ? 'inline-flex' : 'none';
            document.getElementById('next-step').style.display = step < totalSteps ? 'inline-flex' : 'none';
            document.getElementById('submit-product').style.display = step === totalSteps ? 'inline-flex' : 'none';

            // Special handling for review step
            if (step === 4) {
                populateReview();
            }
        }

        function validateCurrentStep() {
            if (currentStep === 1) {
                const requiredFields = ['name', 'sku', 'base_price', 'category_id', 'brand_id'];
                for (let field of requiredFields) {
                    const element = document.getElementById(field);
                    if (!element || !element.value.trim()) {
                        if (element) {
                            element.focus();
                        }
                        alert(`Please fill in the ${field.replace('_', ' ')} field.`);
                        return false;
                    }
                }
            }
            return true;
        }

        // Image upload handling is now managed by the ImageManager component

        // Variant management (similar to create form but with existing variant handling)
        document.getElementById('add-variant').addEventListener('click', addVariant);

        function addVariant() {
            variantCounter++;
            const variantHtml = `
                <div class="variant-item border border-gray-200 rounded-lg p-4 mb-4" data-variant="new-${variantCounter}">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-medium text-gray-900">New Variant ${variantCounter}</h4>
                        <button type="button" onclick="removeVariant('new-${variantCounter}')" class="text-red-600 hover:text-red-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Variant Type</label>
                            <select name="new_variants[${variantCounter}][variant_type]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="color">Color</option>
                                <option value="size">Size</option>
                                <option value="material">Material</option>
                                <option value="style">Style</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Variant Value</label>
                            <input type="text" 
                                   name="new_variants[${variantCounter}][variant_value]" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="e.g., Red, Large, Cotton">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" 
                                   name="new_variants[${variantCounter}][sku]" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Variant SKU">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price Adjustment</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">₱</span>
                                </div>
                                <input type="number" 
                                       name="new_variants[${variantCounter}][price_adjustment]" 
                                       step="0.01"
                                       class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Additional cost for this variant</p>
                        </div>
                        
                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="new_variants[${variantCounter}][is_active]" 
                                       value="1"
                                       checked
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('variants-container').insertAdjacentHTML('beforeend', variantHtml);
        }

        function removeVariant(variantId) {
            const variantElement = document.querySelector(`[data-variant="${variantId}"]`);
            if (variantElement) {
                if (variantId.toString().startsWith('new-')) {
                    // New variant, just remove from DOM
                    variantElement.remove();
                } else {
                    // Existing variant, mark for deletion
                    variantElement.style.display = 'none';
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_variants[]';
                    deleteInput.value = variantId;
                    document.getElementById('product-form').appendChild(deleteInput);
                }
            }
        }

        // Review step population (similar to create form)
        function populateReview() {
            const formData = new FormData(document.getElementById('product-form'));
            let reviewHtml = '<div class="space-y-6">';
            
            // Basic Information
            reviewHtml += `
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Basic Information</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Product Name</dt>
                            <dd class="text-sm text-gray-900">${formData.get('name') || 'Not specified'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">SKU</dt>
                            <dd class="text-sm text-gray-900">${formData.get('sku') || 'Not specified'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Price</dt>
                            <dd class="text-sm text-gray-900">₱${formData.get('base_price') || '0.00'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm text-gray-900">${formData.get('status') || 'Active'}</dd>
                        </div>
                    </dl>
                </div>
            `;
            
            // Images
            const existingImageCount = document.querySelectorAll('.existing-image:not([style*="display: none"])').length;
            const newImageCount = document.getElementById('images').files.length;
            reviewHtml += `
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Images</h4>
                    <p class="text-sm text-gray-600">${existingImageCount} existing image(s), ${newImageCount} new image(s)</p>
                </div>
            `;
            
            // Variants
            const existingVariantCount = document.querySelectorAll('.variant-item:not([style*="display: none"])').length;
            reviewHtml += `
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Variants</h4>
                    <p class="text-sm text-gray-600">${existingVariantCount} variant(s) configured</p>
                </div>
            `;
            
            reviewHtml += '</div>';
            
            document.getElementById('review-content').innerHTML = reviewHtml;
        }

        // Initialize inline creators for category and brand
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.InlineCreator !== 'undefined') {
                const categoryCreator = new window.InlineCreator(
                    'category-modal',
                    'category_id',
                    '{{ route("staff.categories.store-inline") }}'
                );

                const brandCreator = new window.InlineCreator(
                    'brand-modal',
                    'brand_id',
                    '{{ route("staff.brands.store-inline") }}'
                );
            }
        });
    </script>
    @endpush

    <!-- Inline Creation Modals -->
    <x-inline-create-modal 
        type="category" 
        :parentCategories="$categories"
        modalId="category-modal" 
    />

    <x-inline-create-modal 
        type="brand" 
        modalId="brand-modal" 
    />

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        :productId="$product->id"
        :productName="$product->name"
        :stockQuantity="$product->total_stock"
        :deleteRoute="route('staff.products.destroy', $product)"
    />

    @push('scripts')
    <script type="module">
        import { showDeleteModal } from '{{ asset('js/product-deletion.js') }}';
        window.showDeleteModal = showDeleteModal;
    </script>
    <script>
        // Delete Product - Now handled by product-deletion.js modal
        // The showDeleteModal function is imported from product-deletion.js
        function deleteProduct(productId) {
            const productName = '{{ $product->name }}';
            const stockQuantity = {{ $product->total_stock }};
            showDeleteModal(productId, productName, stockQuantity);
        }
    </script>
    @endpush
</x-app-layout>