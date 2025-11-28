/**
 * Analytics Charts Module
 * Provides reusable chart initialization functions for the admin analytics dashboard
 */

import Chart from 'chart.js/auto';

/**
 * Cosmetics Theme Color Palette
 * Pink-Purple-Indigo gradient colors matching the landing page
 */
const cosmeticsColors = {
    // Primary gradient colors
    pink: {
        solid: 'rgb(236, 72, 153)',      // pink-500
        light: 'rgba(236, 72, 153, 0.1)',
        medium: 'rgba(236, 72, 153, 0.5)',
        dark: 'rgb(219, 39, 119)'        // pink-600
    },
    purple: {
        solid: 'rgb(168, 85, 247)',      // purple-500
        light: 'rgba(168, 85, 247, 0.1)',
        medium: 'rgba(168, 85, 247, 0.5)',
        dark: 'rgb(147, 51, 234)'        // purple-600
    },
    indigo: {
        solid: 'rgb(99, 102, 241)',      // indigo-500
        light: 'rgba(99, 102, 241, 0.1)',
        medium: 'rgba(99, 102, 241, 0.5)',
        dark: 'rgb(79, 70, 229)'         // indigo-600
    },
    // Extended palette for charts with multiple data points
    palette: [
        'rgb(236, 72, 153)',   // pink-500
        'rgb(168, 85, 247)',   // purple-500
        'rgb(99, 102, 241)',   // indigo-500
        'rgb(244, 114, 182)',  // pink-400
        'rgb(192, 132, 252)',  // purple-400
        'rgb(129, 140, 248)',  // indigo-400
        'rgb(219, 39, 119)',   // pink-600
        'rgb(147, 51, 234)'    // purple-600
    ]
};

/**
 * Initialize a sales trend line chart
 * @param {string} canvasId - The ID of the canvas element
 * @param {Object} chartData - Chart data containing dates, revenue, and orders arrays
 * @param {string} chartType - Chart type (default: 'line')
 * @returns {Chart} The Chart.js instance
 */
export function initializeSalesChart(canvasId, chartData, chartType = 'line') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.warn(`Canvas element with ID "${canvasId}" not found`);
        return null;
    }

    return new Chart(ctx.getContext('2d'), {
        type: chartType,
        data: {
            labels: chartData.dates || [],
            datasets: [
                {
                    label: 'Revenue',
                    data: chartData.revenue || [],
                    borderColor: cosmeticsColors.pink.solid,
                    backgroundColor: cosmeticsColors.pink.light,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y',
                    pointBackgroundColor: cosmeticsColors.pink.solid,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: cosmeticsColors.pink.solid,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Orders',
                    data: chartData.orders || [],
                    borderColor: cosmeticsColors.purple.solid,
                    backgroundColor: cosmeticsColors.purple.light,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1',
                    pointBackgroundColor: cosmeticsColors.purple.solid,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: cosmeticsColors.purple.solid,
                    pointRadius: 4,
                    pointHoverRadius: 6
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
                    labels: {
                        color: 'rgb(75, 85, 99)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: 'rgb(75, 85, 99)',
                    bodyColor: 'rgb(75, 85, 99)',
                    borderColor: 'rgb(236, 72, 153)',
                    borderWidth: 2,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.dataset.label === 'Revenue') {
                                    label += '₱' + context.parsed.y.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
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
                        },
                        color: 'rgb(107, 114, 128)',
                        font: {
                            size: 11
                        }
                    },
                    title: {
                        display: true,
                        text: 'Revenue',
                        color: cosmeticsColors.pink.solid,
                        font: {
                            size: 12,
                            weight: '600'
                        }
                    },
                    grid: {
                        color: 'rgba(236, 72, 153, 0.1)',
                        drawBorder: false
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
                    ticks: {
                        color: 'rgb(107, 114, 128)',
                        font: {
                            size: 11
                        }
                    },
                    title: {
                        display: true,
                        text: 'Orders',
                        color: cosmeticsColors.purple.solid,
                        font: {
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                x: {
                    ticks: {
                        color: 'rgb(107, 114, 128)',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(168, 85, 247, 0.1)',
                        drawBorder: false
                    }
                }
            }
        }
    });
}

/**
 * Initialize a payment methods pie/doughnut chart
 * @param {string} canvasId - The ID of the canvas element
 * @param {Object} paymentData - Payment data containing labels, data, colors, and percentages
 * @param {string} chartType - Chart type (default: 'doughnut')
 * @returns {Chart} The Chart.js instance
 */
export function initializePaymentMethodsChart(canvasId, paymentData, chartType = 'doughnut') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.warn(`Canvas element with ID "${canvasId}" not found`);
        return null;
    }

    return new Chart(ctx.getContext('2d'), {
        type: chartType,
        data: {
            labels: paymentData.labels || [],
            datasets: [{
                data: paymentData.data || [],
                backgroundColor: paymentData.colors || cosmeticsColors.palette,
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 8,
                hoverBorderWidth: 4
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
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: 'rgb(75, 85, 99)',
                    bodyColor: 'rgb(75, 85, 99)',
                    borderColor: 'rgb(236, 72, 153)',
                    borderWidth: 2,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentage = paymentData.percentages ? 
                                paymentData.percentages[context.dataIndex] : 0;
                            return label + ': ₱' + value.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' (' + percentage.toFixed(1) + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize a category breakdown bar chart
 * @param {string} canvasId - The ID of the canvas element
 * @param {Object} categoryData - Category data containing labels and values
 * @returns {Chart} The Chart.js instance
 */
export function initializeCategoryChart(canvasId, categoryData) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.warn(`Canvas element with ID "${canvasId}" not found`);
        return null;
    }

    return new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: categoryData.labels || [],
            datasets: [{
                label: 'Revenue',
                data: categoryData.data || [],
                backgroundColor: cosmeticsColors.indigo.medium,
                borderColor: cosmeticsColors.indigo.solid,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                hoverBackgroundColor: cosmeticsColors.indigo.solid,
                hoverBorderColor: cosmeticsColors.indigo.dark,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: 'rgb(75, 85, 99)',
                    bodyColor: 'rgb(75, 85, 99)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 2,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.x.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        },
                        color: 'rgb(107, 114, 128)',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(99, 102, 241, 0.1)',
                        drawBorder: false
                    }
                },
                y: {
                    ticks: {
                        color: 'rgb(75, 85, 99)',
                        font: {
                            size: 11,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Destroy a chart instance safely
 * @param {Chart} chartInstance - The Chart.js instance to destroy
 */
export function destroyChart(chartInstance) {
    if (chartInstance && typeof chartInstance.destroy === 'function') {
        chartInstance.destroy();
    }
}

/**
 * Update chart data dynamically
 * @param {Chart} chartInstance - The Chart.js instance to update
 * @param {Object} newData - New data to update the chart with
 */
export function updateChartData(chartInstance, newData) {
    if (!chartInstance) {
        console.warn('Chart instance is null or undefined');
        return;
    }

    if (newData.labels) {
        chartInstance.data.labels = newData.labels;
    }

    if (newData.datasets) {
        chartInstance.data.datasets = newData.datasets;
    } else if (newData.data) {
        // For simple data updates
        chartInstance.data.datasets.forEach((dataset, index) => {
            if (newData.data[index]) {
                dataset.data = newData.data[index];
            }
        });
    }

    chartInstance.update();
}

// Make functions available globally for use in Blade templates
window.initializeSalesChart = initializeSalesChart;
window.initializePaymentMethodsChart = initializePaymentMethodsChart;
window.initializeCategoryChart = initializeCategoryChart;
window.destroyChart = destroyChart;
window.updateChartData = updateChartData;

// Make Chart.js available globally
window.Chart = Chart;
