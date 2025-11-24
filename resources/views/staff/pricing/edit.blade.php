<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Pricing') }} - {{ $product->name }}
            </h2>
            <a href="{{ route('staff.pricing.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Product Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Product Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">SKU</p>
                            <p class="font-medium">{{ $product->sku }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Category</p>
                            <p class="font-medium">{{ $product->category->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Brand</p>
                            <p class="font-medium">{{ $product->brand->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="font-medium">{{ ucfirst($product->status) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Base Product Pricing -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Base Product Pricing</h3>
                    <form method="POST" action="{{ route('staff.pricing.update', $product) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="base_price" class="block text-sm font-medium text-gray-700">
                                    Base Price <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="base_price" id="base_price" 
                                           value="{{ old('base_price', $product->base_price) }}"
                                           class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           required>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Regular selling price</p>
                            </div>

                            <div>
                                <label for="sale_price" class="block text-sm font-medium text-gray-700">
                                    Sale Price
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="sale_price" id="sale_price" 
                                           value="{{ old('sale_price', $product->sale_price) }}"
                                           class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Discounted price (optional)</p>
                            </div>

                            <div>
                                <label for="cost_price" class="block text-sm font-medium text-gray-700">
                                    Cost Price
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <input type="number" step="0.01" name="cost_price" id="cost_price" 
                                           value="{{ old('cost_price', $product->cost_price) }}"
                                           class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Your cost (optional)</p>
                            </div>
                        </div>

                        <!-- Pricing Summary -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium mb-2">Pricing Summary</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Effective Price:</span>
                                    <span class="font-semibold ml-2">₱{{ number_format($product->effective_price, 2) }}</span>
                                </div>
                                @if($product->is_on_sale)
                                    <div>
                                        <span class="text-gray-600">Discount:</span>
                                        <span class="font-semibold ml-2 text-red-600">{{ $product->discount_percentage }}% off</span>
                                    </div>
                                @endif
                                @if($product->cost_price)
                                    <div>
                                        <span class="text-gray-600">Profit Margin:</span>
                                        <span class="font-semibold ml-2">
                                            ₱{{ number_format($product->effective_price - $product->cost_price, 2) }}
                                            ({{ number_format((($product->effective_price - $product->cost_price) / $product->effective_price) * 100, 1) }}%)
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Pricing
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Variant Pricing -->
            @if($product->variants->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Variant Pricing</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Variant prices are calculated as: Base Price + Price Adjustment
                        </p>

                        <div class="space-y-4">
                            @foreach($product->variants as $variant)
                                <div class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-medium">{{ $variant->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $variant->display_name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">SKU: {{ $variant->sku }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">Effective Price</p>
                                            <p class="text-lg font-semibold">₱{{ number_format($variant->effective_price, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <form class="variant-pricing-form" data-variant-id="{{ $variant->id }}">
                                            @csrf
                                            <div class="flex items-end gap-4">
                                                <div class="flex-1">
                                                    <label class="block text-sm font-medium text-gray-700">
                                                        Price Adjustment
                                                    </label>
                                                    <div class="mt-1 relative rounded-md shadow-sm">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                            <span class="text-gray-500 sm:text-sm">₱</span>
                                                        </div>
                                                        <input type="number" step="0.01" name="price_adjustment" 
                                                               value="{{ $variant->price_adjustment }}"
                                                               class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        Use negative values for discounts
                                                    </p>
                                                </div>
                                                <div>
                                                    <button type="submit" 
                                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                        Update
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Handle variant pricing updates via AJAX
        document.querySelectorAll('.variant-pricing-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const variantId = this.dataset.variantId;
                const priceAdjustment = this.querySelector('[name="price_adjustment"]').value;
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.textContent;
                
                button.disabled = true;
                button.textContent = 'Updating...';
                
                try {
                    const response = await fetch(`{{ route('staff.pricing.index') }}/${variantId}/variant`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
                        },
                        body: JSON.stringify({ price_adjustment: priceAdjustment })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
                        alert.textContent = data.message;
                        document.body.appendChild(alert);
                        
                        setTimeout(() => alert.remove(), 3000);
                        
                        // Update effective price display
                        const priceDisplay = this.closest('.border').querySelector('.text-lg.font-semibold');
                        priceDisplay.textContent = '$' + parseFloat(data.effective_price).toFixed(2);
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Failed to update variant pricing');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
