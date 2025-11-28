@props(['paymentData', 'title' => 'Payment Methods Distribution', 'chartId' => 'paymentMethodsChart'])

<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-shadow duration-300">
    <div class="p-6">
        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent mb-4">{{ $title }}</h3>
        
        @if($paymentData && (isset($paymentData['labels']) && count($paymentData['labels']) > 0))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Chart -->
                <div class="relative" style="height: 250px;">
                    <canvas id="{{ $chartId }}"></canvas>
                </div>
                
                <!-- Legend with details -->
                <div class="space-y-3">
                    @foreach($paymentData['labels'] as $index => $label)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-pink-50 to-purple-50 rounded-lg hover:from-pink-100 hover:to-purple-100 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3" 
                                     style="background-color: {{ $paymentData['colors'][$index] ?? '#EC4899' }}">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $label }}</p>
                                    @if(isset($paymentData['percentages'][$index]))
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($paymentData['percentages'][$index], 1) }}%
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if(isset($paymentData['amounts'][$index]))
                                    <p class="text-sm font-semibold text-gray-900">
                                        ₱{{ number_format($paymentData['amounts'][$index], 2) }}
                                    </p>
                                @endif
                                @if(isset($paymentData['counts'][$index]))
                                    <p class="text-xs text-gray-500">
                                        {{ number_format($paymentData['counts'][$index]) }} orders
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No payment method data available for this period.</p>
            </div>
        @endif
    </div>
</div>

@if($paymentData && isset($paymentData['labels']) && count($paymentData['labels']) > 0)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
        const paymentData = @json($paymentData);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: paymentData.labels || [],
                datasets: [{
                    data: paymentData.data || [],
                    backgroundColor: paymentData.colors || [
                        '#EC4899', '#A855F7', '#6366F1', '#DB2777', '#9333EA', '#4F46E5'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = paymentData.percentages ? paymentData.percentages[context.dataIndex] : 0;
                                return label + ': ₱' + value.toLocaleString() + ' (' + percentage.toFixed(1) + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endif
