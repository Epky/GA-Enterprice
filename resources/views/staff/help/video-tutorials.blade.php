<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Video Tutorials') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Introduction -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-2">Learn by Watching</h3>
                    <p class="text-gray-600">
                        Watch step-by-step video tutorials to master the product management system. 
                        Each video covers a specific topic with practical examples.
                    </p>
                </div>
            </div>

            <!-- Tutorial Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Getting Started -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold">Getting Started</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">1</span>
                                <div>
                                    <div class="font-medium">Dashboard Overview</div>
                                    <div class="text-sm text-gray-600">5 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">2</span>
                                <div>
                                    <div class="font-medium">Navigation and Interface</div>
                                    <div class="text-sm text-gray-600">7 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">3</span>
                                <div>
                                    <div class="font-medium">Your First Product</div>
                                    <div class="text-sm text-gray-600">10 minutes</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Product Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold">Product Management</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">1</span>
                                <div>
                                    <div class="font-medium">Creating Products</div>
                                    <div class="text-sm text-gray-600">12 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">2</span>
                                <div>
                                    <div class="font-medium">Managing Variants</div>
                                    <div class="text-sm text-gray-600">8 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">3</span>
                                <div>
                                    <div class="font-medium">Bulk Operations</div>
                                    <div class="text-sm text-gray-600">6 minutes</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Inventory Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold">Inventory Management</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">1</span>
                                <div>
                                    <div class="font-medium">Stock Updates</div>
                                    <div class="text-sm text-gray-600">9 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">2</span>
                                <div>
                                    <div class="font-medium">Low Stock Alerts</div>
                                    <div class="text-sm text-gray-600">5 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">3</span>
                                <div>
                                    <div class="font-medium">Inventory Reports</div>
                                    <div class="text-sm text-gray-600">7 minutes</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Image Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold">Image Management</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">1</span>
                                <div>
                                    <div class="font-medium">Uploading Images</div>
                                    <div class="text-sm text-gray-600">6 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">2</span>
                                <div>
                                    <div class="font-medium">Image Optimization</div>
                                    <div class="text-sm text-gray-600">8 minutes</div>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium mr-3">3</span>
                                <div>
                                    <div class="font-medium">Gallery Management</div>
                                    <div class="text-sm text-gray-600">5 minutes</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Coming Soon Notice -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-900">Video Tutorials Coming Soon</h3>
                        <p class="mt-2 text-sm text-yellow-800">
                            We're currently producing high-quality video tutorials for all features. 
                            In the meantime, please refer to our comprehensive written guides and quick reference materials.
                        </p>
                        <div class="mt-4 flex space-x-4">
                            <a href="{{ route('staff.help.quick-reference') }}" class="text-sm font-medium text-yellow-700 hover:text-yellow-600">
                                Quick Reference Guide →
                            </a>
                            <a href="{{ asset('docs/STAFF_PRODUCT_MANAGEMENT_GUIDE.md') }}" target="_blank" class="text-sm font-medium text-yellow-700 hover:text-yellow-600">
                                User Guide →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
