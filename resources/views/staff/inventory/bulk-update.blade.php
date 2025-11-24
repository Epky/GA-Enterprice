<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bulk Stock Update') }}
            </h2>
            <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Inventory
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('warning'))
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">{{ session('warning') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Instructions -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Bulk Stock Update Instructions</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Search and select products to update</li>
                                <li>Enter quantity changes for each product</li>
                                <li>Select movement type and reason for audit trail</li>
                                <li>All updates are processed atomically (all succeed or all fail)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Update Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form id="bulkUpdateForm" action="{{ route('staff.inventory.bulk-update') }}" method="POST">
                        @csrf

                        <!-- Product Search and Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Search and Add Products
                            </label>
                            <div class="flex space-x-2">
                                <input type="text" id="productSearch" placeholder="Search by product name or SKU..." class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" id="searchBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Search
                                </button>
                            </div>
                            <div id="searchResults" class="mt-2 hidden">
                                <!-- Search results will be populated here -->
                            </div>
                        </div>

                        <!-- Selected Products Table -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Selected Products</h3>
                                <span id="selectedCount" class="text-sm text-gray-500">0 products selected</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement Type</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedProducts" class="bg-white divide-y divide-gray-200">
                                        <tr id="emptyState">
                                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                                No products selected. Use the search above to add products.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Global Settings -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Apply to All Products</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="globalMovementType" class="block text-sm font-medium text-gray-700 mb-1">
                                        Movement Type
                                    </label>
                                    <select id="globalMovementType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select type...</option>
                                        <option value="purchase">Purchase/Restock</option>
                                        <option value="sale">Sale</option>
                                        <option value="return">Return</option>
                                        <option value="damage">Damage/Loss</option>
                                        <option value="adjustment">Adjustment</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="globalLocation" class="block text-sm font-medium text-gray-700 mb-1">
                                        Location
                                    </label>
                                    <select id="globalLocation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select location...</option>
                                        <option value="main_warehouse">Main Warehouse</option>
                                        <option value="store_front">Store Front</option>
                                        <option value="online_fulfillment">Online Fulfillment</option>
                                        <option value="returns_area">Returns Area</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="globalNotes" class="block text-sm font-medium text-gray-700 mb-1">
                                        Notes
                                    </label>
                                    <input type="text" id="globalNotes" placeholder="Apply notes to all..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" id="applyGlobalBtn" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Apply to All Products
                                </button>
                            </div>
                        </div>

                        <!-- Hidden input for updates JSON -->
                        <input type="hidden" name="updates" id="updatesInput">

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <div class="text-sm text-gray-500">
                                <span id="validationMessage"></span>
                            </div>
                            <div class="flex space-x-3">
                                <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit" id="submitBtn" disabled class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Stock
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // State management
        let selectedProducts = [];
        let allProducts = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            setupEventListeners();
        });

        // Load all products for search
        async function loadProducts() {
            try {
                const response = await fetch('{{ route("staff.products.data") }}');
                const data = await response.json();
                allProducts = data.data || [];
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('searchBtn').addEventListener('click', performSearch);
            document.getElementById('productSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
            document.getElementById('applyGlobalBtn').addEventListener('click', applyGlobalSettings);
            document.getElementById('bulkUpdateForm').addEventListener('submit', handleSubmit);
        }

        // Perform product search
        function performSearch() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase();
            if (!searchTerm) return;

            const results = allProducts.filter(product => 
                product.name.toLowerCase().includes(searchTerm) || 
                product.sku.toLowerCase().includes(searchTerm)
            ).slice(0, 10);

            displaySearchResults(results);
        }

        // Display search results
        function displaySearchResults(results) {
            const resultsDiv = document.getElementById('searchResults');
            
            if (results.length === 0) {
                resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 bg-white border border-gray-200 rounded-md">No products found</div>';
                resultsDiv.classList.remove('hidden');
                return;
            }

            const html = results.map(product => `
                <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer" onclick="addProduct(${product.id})">
                    <div>
                        <p class="text-sm font-medium text-gray-900">${product.name}</p>
                        <p class="text-xs text-gray-500">SKU: ${product.sku}</p>
                    </div>
                    <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Add
                    </button>
                </div>
            `).join('');

            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        }

        // Add product to selection
        function addProduct(productId) {
            const product = allProducts.find(p => p.id === productId);
            if (!product) return;

            // Check if already added
            if (selectedProducts.find(p => p.product_id === productId)) {
                alert('Product already added');
                return;
            }

            selectedProducts.push({
                product_id: productId,
                product_name: product.name,
                product_sku: product.sku,
                current_stock: product.inventory?.quantity_available || 0,
                quantity: 0,
                movement_type: '',
                location: 'main_warehouse',
                variant_id: null,
                notes: ''
            });

            renderSelectedProducts();
            document.getElementById('searchResults').classList.add('hidden');
            document.getElementById('productSearch').value = '';
        }

        // Render selected products table
        function renderSelectedProducts() {
            const tbody = document.getElementById('selectedProducts');
            const emptyState = document.getElementById('emptyState');

            if (selectedProducts.length === 0) {
                emptyState.classList.remove('hidden');
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('selectedCount').textContent = '0 products selected';
                return;
            }

            emptyState.classList.add('hidden');
            document.getElementById('selectedCount').textContent = `${selectedProducts.length} product${selectedProducts.length > 1 ? 's' : ''} selected`;

            const html = selectedProducts.map((product, index) => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${product.product_name}</div>
                        <div class="text-xs text-gray-500">${product.product_sku}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${product.current_stock}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <select onchange="updateProduct(${index}, 'movement_type', this.value)" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Select...</option>
                            <option value="purchase" ${product.movement_type === 'purchase' ? 'selected' : ''}>Purchase</option>
                            <option value="sale" ${product.movement_type === 'sale' ? 'selected' : ''}>Sale</option>
                            <option value="return" ${product.movement_type === 'return' ? 'selected' : ''}>Return</option>
                            <option value="damage" ${product.movement_type === 'damage' ? 'selected' : ''}>Damage</option>
                            <option value="adjustment" ${product.movement_type === 'adjustment' ? 'selected' : ''}>Adjustment</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="number" value="${product.quantity}" onchange="updateProduct(${index}, 'quantity', parseInt(this.value))" class="w-24 text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <select onchange="updateProduct(${index}, 'location', this.value)" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="main_warehouse" ${product.location === 'main_warehouse' ? 'selected' : ''}>Main Warehouse</option>
                            <option value="store_front" ${product.location === 'store_front' ? 'selected' : ''}>Store Front</option>
                            <option value="online_fulfillment" ${product.location === 'online_fulfillment' ? 'selected' : ''}>Online Fulfillment</option>
                            <option value="returns_area" ${product.location === 'returns_area' ? 'selected' : ''}>Returns Area</option>
                        </select>
                    </td>
                    <td class="px-6 py-4">
                        <input type="text" value="${product.notes}" onchange="updateProduct(${index}, 'notes', this.value)" placeholder="Optional notes..." class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button type="button" onclick="removeProduct(${index})" class="text-red-600 hover:text-red-900">
                            Remove
                        </button>
                    </td>
                </tr>
            `).join('');

            tbody.innerHTML = html;
            validateForm();
        }

        // Update product field
        function updateProduct(index, field, value) {
            selectedProducts[index][field] = value;
            validateForm();
        }

        // Remove product from selection
        function removeProduct(index) {
            selectedProducts.splice(index, 1);
            renderSelectedProducts();
        }

        // Apply global settings to all products
        function applyGlobalSettings() {
            const movementType = document.getElementById('globalMovementType').value;
            const location = document.getElementById('globalLocation').value;
            const notes = document.getElementById('globalNotes').value;

            selectedProducts.forEach(product => {
                if (movementType) product.movement_type = movementType;
                if (location) product.location = location;
                if (notes) product.notes = notes;
            });

            renderSelectedProducts();
        }

        // Validate form
        function validateForm() {
            const isValid = selectedProducts.length > 0 && selectedProducts.every(product => 
                product.quantity !== 0 && 
                product.movement_type && 
                product.location
            );

            document.getElementById('submitBtn').disabled = !isValid;
            
            if (!isValid && selectedProducts.length > 0) {
                document.getElementById('validationMessage').textContent = 'Please fill in all required fields for each product';
                document.getElementById('validationMessage').classList.add('text-red-600');
            } else {
                document.getElementById('validationMessage').textContent = '';
            }
        }

        // Handle form submission
        function handleSubmit(e) {
            e.preventDefault();
            
            if (selectedProducts.length === 0) {
                alert('Please add at least one product');
                return;
            }

            // Prepare updates data
            const updates = selectedProducts.map(product => ({
                product_id: product.product_id,
                quantity: product.quantity,
                movement_type: product.movement_type,
                location: product.location,
                variant_id: product.variant_id,
                notes: product.notes
            }));

            // Set hidden input value
            document.getElementById('updatesInput').value = JSON.stringify(updates);

            // Submit form
            e.target.submit();
        }
    </script>
    @endpush
</x-app-layout>
