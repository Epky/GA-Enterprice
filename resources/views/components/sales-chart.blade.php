@props(['chartData', 'chartType' => 'line', 'title' => 'Sales Trend', 'chartId' => 'salesChart'])

<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl hover:shadow-2xl transition-shadow duration-300">
    <div class="p-6">
        <h3 class="text-lg font-semibold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent mb-4">{{ $title }}</h3>
        <div class="relative" style="height: 300px;">
            <canvas id="{{ $chartId }}"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: '{{ $chartType }}',
            data: {
                labels: chartData.dates || [],
                datasets: [
                    {
                        label: 'Revenue',
                        data: chartData.revenue || [],
                        borderColor: 'rgb(236, 72, 153)', // pink-500
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: chartData.orders || [],
                        borderColor: 'rgb(168, 85, 247)', // purple-500
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.dataset.label === 'Revenue') {
                                        label += '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    } else {
                                        label += context.parsed.y.toLocaleString();
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        },
                        title: {
                            display: true,
                            text: 'Revenue'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
