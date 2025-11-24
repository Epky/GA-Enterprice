<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quick Reference Guide') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('staff.products.create') }}" class="flex items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <div>
                                <div class="font-semibold">Add Product</div>
                                <div class="text-sm text-gray-600">Create new product</div>
                            </div>
                        </a>
                        <a href="{{ route('staff.inventory.index') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <div>
                                <div class="font-semibold">Update Stock</div>
                                <div class="text-sm text-gray-600">Manage inventory</div>
                            </div>
                        </a>
                        <a href="{{ route('staff.products.index') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <div>
                                <div class="font-semibold">Find Product</div>
                                <div class="text-sm text-gray-600">Search catalog</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Keyboard Shortcuts</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span>Save Form</span>
                            <kbd class="px-2 py-1 bg-gray-200 rounded text-sm font-mono">Ctrl + S</kbd>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span>Focus Search</span>
                            <kbd class="px-2 py-1 bg-gray-200 rounded text-sm font-mono">Ctrl + F</kbd>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span>Close Modal</span>
                            <kbd class="px-2 py-1 bg-gray-200 rounded text-sm font-mono">Esc</kbd>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span>Navigate Fields</span>
                            <kbd class="px-2 py-1 bg-gray-200 rounded text-sm font-mono">Tab</kbd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Common Tasks -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Common Tasks</h3>
                    <div class="space-y-4">
                        <div class="border-l-4 border-indigo-500 pl-4">
                            <h4 class="font-semibold mb-2">Adding a New Product</h4>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600">
                                <li>Click "Add Product" button</li>
                                <li>Fill in product name, SKU, and category</li>
                                <li>Set pricing and initial stock</li>
                                <li>Upload product images</li>
                                <li>Add variants if needed</li>
                                <li>Save and review</li>
                            </ol>
                        </div>
                        <div class="border-l-4 border-green-500 pl-4">
                            <h4 class="font-semibold mb-2">Updating Stock Levels</h4>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600">
                                <li>Go to Inventory section</li>
                                <li>Find product by search or filter</li>
                                <li>Click "Update Stock"</li>
                                <li>Enter new quantity and movement type</li>
                                <li>Add notes for reference</li>
                                <li>Save changes</li>
                            </ol>
                        </div>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h4 class="font-semibold mb-2">Managing Product Images</h4>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600">
                                <li>Edit product</li>
                                <li>Scroll to Images section</li>
                                <li>Drag and drop or click to upload</li>
                                <li>Set primary image (star icon)</li>
                                <li>Reorder by dragging</li>
                                <li>Add alt text for SEO</li>
                            </ol>
                        </div>
                        <div class="border-l-4 border-yellow-500 pl-4">
                            <h4 class="font-semibold mb-2">Creating Product Variants</h4>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600">
                                <li>Create or edit product</li>
                                <li>Scroll to Variants section</li>
                                <li>Click "Add Variant"</li>
                                <li>Enter variant name and SKU</li>
                                <li>Set variant price or adjustment</li>
                                <li>Set variant stock level</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field Requirements -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Field Requirements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Example</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">Product Name</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">Yes</td>
                                    <td class="px-6 py-4 text-sm">Text, 3-255 chars</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">Hydrating Face Cream</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">SKU</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">No (auto)</td>
                                    <td class="px-6 py-4 text-sm">Alphanumeric, unique</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">HFC-001</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">Price</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">Yes</td>
                                    <td class="px-6 py-4 text-sm">Decimal, positive</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">29.99</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">Category</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">Yes</td>
                                    <td class="px-6 py-4 text-sm">Select from list</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">Face Care</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">Images</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">Recommended</td>
                                    <td class="px-6 py-4 text-sm">JPEG/PNG/WebP, max 5MB</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">product.jpg</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">Stock Quantity</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">Yes</td>
                                    <td class="px-6 py-4 text-sm">Whole number, ≥ 0</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">100</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Status Indicators -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Status Indicators</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Active</span>
                            <span class="text-sm text-gray-600">Product is visible and purchasable</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">Inactive</span>
                            <span class="text-sm text-gray-600">Product is hidden from customers</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Out of Stock</span>
                            <span class="text-sm text-gray-600">Product visible but not purchasable</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Low Stock</span>
                            <span class="text-sm text-gray-600">Stock below reorder level</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Featured</span>
                            <span class="text-sm text-gray-600">Highlighted in special sections</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">New Arrival</span>
                            <span class="text-sm text-gray-600">Recently added product</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips and Best Practices -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Tips and Best Practices</h3>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Always set reorder levels to avoid stockouts</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Use high-quality images (1200x1200px recommended)</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Write detailed product descriptions with keywords</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Add notes when adjusting inventory for audit trail</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Use consistent naming conventions for SKUs</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Preview products before publishing to customers</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Resources -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Help Resources</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ asset('docs/STAFF_PRODUCT_MANAGEMENT_GUIDE.md') }}" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 transition">
                            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <div>
                                <div class="font-semibold">User Guide</div>
                                <div class="text-sm text-gray-600">Complete documentation</div>
                            </div>
                        </a>
                        <a href="{{ asset('docs/TROUBLESHOOTING_GUIDE.md') }}" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 transition">
                            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <div>
                                <div class="font-semibold">Troubleshooting</div>
                                <div class="text-sm text-gray-600">Common issues & fixes</div>
                            </div>
                        </a>
                        <a href="mailto:support@beautystore.com" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 transition">
                            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <div class="font-semibold">Contact Support</div>
                                <div class="text-sm text-gray-600">Get help from team</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
