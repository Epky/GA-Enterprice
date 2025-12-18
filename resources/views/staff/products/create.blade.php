<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Product') }}
            </h2>
            <div class="flex space-x-3">
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
            <!-- Flash Messages -->
            @if (session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if (session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <form action="{{ route('staff.products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
                @csrf

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
                                        value="{{ old('name') }}"
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
                                        value="{{ old('sku') }}"
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
                                            value="{{ old('base_price') }}"
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
                                        :selected="old('category_id')"
                                        :required="true"
                                        deleteRoute="staff.categories.delete-inline"
                                        refreshRoute="staff.categories.active"
                                        placeholder="Select Category" />
                                    <div class="mt-2">
                                        <x-add-inline-button
                                            target="category-modal"
                                            label="Add New Category" />
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
                                        :selected="old('brand_id')"
                                        :required="true"
                                        deleteRoute="staff.brands.delete-inline"
                                        refreshRoute="staff.brands.active"
                                        placeholder="Select Brand" />
                                    <div class="mt-2">
                                        <x-add-inline-button
                                            target="brand-modal"
                                            label="Add New Brand" />
                                    </div>
                                    @error('brand_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                    <textarea name="description"
                                        id="description"
                                        rows="4"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Enter product description">{{ old('description') }}</textarea>
                                    @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
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
                                            {{ old('is_featured') ? 'checked' : '' }}
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
                                :product="null"
                                :existingImages="[]"
                                name="images"
                                :maxFiles="10"
                                :required="false" />
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

                            <!-- Variants Container -->
                            <div id="variants-container">
                                <!-- Variants will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="form-step" id="step-4" style="display: none;">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Review Product Details</h3>

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
                                    Create Product
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
        let variantCounter = 0;



        // Step navigation
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
                const requiredFields = [{
                        id: 'name',
                        label: 'Product Name'
                    },
                    {
                        id: 'sku',
                        label: 'SKU'
                    },
                    {
                        id: 'base_price',
                        label: 'Price'
                    },
                    {
                        id: 'category_id',
                        label: 'Category'
                    },
                    {
                        id: 'brand_id',
                        label: 'Brand'
                    },
                    {
                        id: 'description',
                        label: 'Description'
                    }
                ];

                for (let field of requiredFields) {
                    const element = document.getElementById(field.id);

                    if (!element) {
                        console.warn(`Element not found: ${field.id}`);
                        continue;
                    }

                    const value = element.value ? element.value.trim() : '';

                    // Check if field is empty
                    if (value === '') {
                        element.focus();
                        element.classList.add('border-red-500');
                        alert(`Please fill in the ${field.label} field.`);
                        return false;
                    }

                    // Additional check for price - must be greater than 0
                    if (field.id === 'base_price' && parseFloat(value) <= 0) {
                        element.focus();
                        element.classList.add('border-red-500');
                        alert(`${field.label} must be greater than 0.`);
                        return false;
                    }

                    element.classList.remove('border-red-500');
                }
            }
            return true;
        }

        // Image upload handling is managed by the ImageManager component

        // Variant management
        document.getElementById('add-variant').addEventListener('click', () => addVariant());

        function addVariant(data = null) {
            variantCounter++;

            const type = data?.variant_type || 'color';
            const val = data?.variant_value || '';
            const skuVal = data?.sku || '';
            const priceVal = data?.price_adjustment || '';
            const stockVal = data?.stock_quantity || '';
            const isActive = data ? (String(data.is_active) === '1' || data.is_active === true) : true;

            const variantHtml = `
                <div class="variant-item border border-gray-200 rounded-lg p-4 mb-4" data-variant="${variantCounter}">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-medium text-gray-900">Variant ${variantCounter}</h4>
                        <button type="button" onclick="removeVariant(${variantCounter})" class="text-red-600 hover:text-red-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Variant Type</label>
                            <select name="variants[${variantCounter}][variant_type]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="color" ${type === 'color' ? 'selected' : ''}>Color</option>
                                <option value="size" ${type === 'size' ? 'selected' : ''}>Size</option>
                                <option value="material" ${type === 'material' ? 'selected' : ''}>Material</option>
                                <option value="style" ${type === 'style' ? 'selected' : ''}>Style</option>
                                <option value="other" ${type === 'other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Variant Value</label>
                            <input type="text" 
                                   name="variants[${variantCounter}][variant_value]" 
                                   value="${val}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="e.g., Red, Large, Cotton">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" 
                                   name="variants[${variantCounter}][sku]" 
                                   value="${skuVal}"
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
                                       name="variants[${variantCounter}][price_adjustment]" 
                                       value="${priceVal}"
                                       step="0.01"
                                       class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Additional cost for this variant</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                            <input type="number" 
                                   name="variants[${variantCounter}][stock_quantity]" 
                                   value="${stockVal}"
                                   min="0"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="0">
                        </div>
                        
                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="variants[${variantCounter}][is_active]" 
                                       value="1"
                                       ${isActive ? 'checked' : ''}
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
                variantElement.remove();
            }
        }

        // Review step population
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
            // Helper to count files in the image-manager input
            const imageInput = document.getElementById('images');
            const imageCount = imageInput && imageInput.files ? imageInput.files.length : 0;

            reviewHtml += `
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Images</h4>
                    <p class="text-sm text-gray-600">${imageCount} image(s) selected</p>
                </div>
            `;

            // Variants
            const variantCount = document.querySelectorAll('.variant-item').length;
            reviewHtml += `
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Variants</h4>
                    <p class="text-sm text-gray-600">${variantCount} variant(s) configured</p>
                </div>
            `;

            reviewHtml += '</div>';

            document.getElementById('review-content').innerHTML = reviewHtml;
        }

        // Auto-generate SKU from product name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const sku = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
            if (sku && !document.getElementById('sku').value) {
                document.getElementById('sku').value = sku + '-' + Math.random().toString(36).substr(2, 4).toUpperCase();
            }
        });

        // Initialize inline creators for category and brand
        document.addEventListener('DOMContentLoaded', function() {
            // Restore variants from validation error
            const oldVariants = @json(old('variants', []));
            const variantsData = typeof oldVariants === 'object' && oldVariants !== null ?
                Object.values(oldVariants) : [];

            if (variantsData.length > 0) {
                variantsData.forEach(variant => {
                    addVariant(variant);
                });
            }

            // Show calculated current step
            if (currentStep > 1) {
                showStep(currentStep);
            }

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
        modalId="category-modal" />

    <x-inline-create-modal
        type="brand"
        modalId="brand-modal" />
</x-app-layout>