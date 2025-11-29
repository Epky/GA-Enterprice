<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Parse time period string into date range.
     * 
     * @param string $period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    public function parseDateRange(string $period, ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        $now = Carbon::now();
        
        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'custom' => [
                'start' => $customStart ?? $now->copy()->startOfMonth(),
                'end' => $customEnd ?? $now->copy()->endOfMonth(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Get the previous period date range for comparison.
     * 
     * @param Carbon $startDate Current period start date
     * @param Carbon $endDate Current period end date
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    public function getPreviousPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $periodLength = $startDate->diffInDays($endDate);
        
        return [
            'start' => $startDate->copy()->subDays($periodLength + 1),
            'end' => $startDate->copy()->subDay(),
        ];
    }

    /**
     * Generate cache key for analytics data.
     * 
     * @param string $metric The metric name (e.g., 'revenue', 'orders')
     * @param string $period The period identifier
     * @param Carbon $date The date for the metric
     * @return string
     */
    protected function getCacheKey(string $metric, string $period, Carbon $date): string
    {
        return sprintf(
            'analytics:%s:%s:%s',
            $metric,
            $period,
            $date->format('Y-m-d')
        );
    }

    /**
     * Get cache TTL based on whether the period is current or past.
     * 
     * @param Carbon $endDate The end date of the period
     * @return int TTL in seconds
     */
    protected function getCacheTTL(Carbon $endDate): int
    {
        // If the period end date is in the past, cache for 24 hours
        // Otherwise, cache for 15 minutes
        return $endDate->isPast() ? 86400 : 900;
    }

    /**
     * Cache analytics data with appropriate TTL.
     * 
     * @param string $metric The metric name
     * @param string $period The period identifier
     * @param Carbon $date The date for the metric
     * @param callable $callback The callback to execute if cache miss
     * @return mixed
     */
    protected function cacheAnalytics(string $metric, string $period, Carbon $date, callable $callback): mixed
    {
        $cacheKey = $this->getCacheKey($metric, $period, $date);
        $ttl = $this->getCacheTTL($date);
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear analytics cache for a specific metric and period.
     * 
     * @param string $metric The metric name
     * @param string $period The period identifier
     * @param Carbon $date The date for the metric
     * @return bool
     */
    public function clearCache(string $metric, string $period, Carbon $date): bool
    {
        $cacheKey = $this->getCacheKey($metric, $period, $date);
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all analytics caches.
     * 
     * @return void
     */
    public function clearAllCaches(): void
    {
        // Clear all analytics cache keys
        // This is a simple implementation; for production, consider using cache tags
        Cache::flush();
    }

    /**
     * Calculate total revenue for a given period.
     * 
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array ['total' => float, 'previous_total' => float, 'change_percent' => float]
     */
    public function calculateRevenue(Carbon $startDate, Carbon $endDate): array
    {
        try {
            return $this->cacheAnalytics('revenue', $startDate->format('Y-m-d'), $endDate, function () use ($startDate, $endDate) {
                // Calculate current period revenue
                $total = \App\Models\Order::where('order_status', 'completed')
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount');

                // Get previous period dates
                $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
                
                // Calculate previous period revenue
                $previousTotal = \App\Models\Order::where('order_status', 'completed')
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
                    ->sum('total_amount');

                // Calculate percentage change
                $changePercent = $this->calculatePercentageChange($total, $previousTotal);

                return [
                    'total' => (float) $total,
                    'previous_total' => (float) $previousTotal,
                    'change_percent' => $changePercent,
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error calculating revenue: ' . $e->getMessage(), [
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'total' => 0.0,
                'previous_total' => 0.0,
                'change_percent' => 0.0,
            ];
        }
    }

    /**
     * Calculate percentage change between two values.
     * 
     * @param float $current Current period value
     * @param float $previous Previous period value
     * @return float Percentage change
     */
    protected function calculatePercentageChange(float $current, float $previous): float
    {
        // Handle division by zero
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Format currency value with symbol and two decimal places.
     * 
     * @param float $amount The amount to format
     * @param string $currency The currency symbol (default: '₱')
     * @return string Formatted currency string
     */
    public function formatCurrency(float $amount, string $currency = '₱'): string
    {
        return $currency . number_format($amount, 2);
    }

    /**
     * Get order metrics for a given period.
     * 
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array [
     *   'total_orders' => int,
     *   'completed_orders' => int,
     *   'pending_orders' => int,
     *   'processing_orders' => int,
     *   'walk_in_orders' => int,
     *   'online_orders' => int,
     *   'avg_order_value' => float
     * ]
     */
    public function getOrderMetrics(Carbon $startDate, Carbon $endDate): array
    {
        try {
            return $this->cacheAnalytics('order_metrics', $startDate->format('Y-m-d'), $endDate, function () use ($startDate, $endDate) {
                // Use database aggregation instead of in-memory filtering for better performance
                // Get counts by status using a single query
                $statusCounts = \App\Models\Order::selectRaw('
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN order_status = "completed" THEN 1 ELSE 0 END) as completed_orders,
                        SUM(CASE WHEN order_status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN order_status = "processing" THEN 1 ELSE 0 END) as processing_orders,
                        SUM(CASE WHEN order_type = "walk_in" THEN 1 ELSE 0 END) as walk_in_orders,
                        SUM(CASE WHEN order_type = "online" THEN 1 ELSE 0 END) as online_orders,
                        SUM(CASE WHEN order_status = "completed" THEN total_amount ELSE 0 END) as total_revenue
                    ')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotIn('order_status', ['cancelled'])
                    ->first();

                // Extract values with defaults
                $totalOrders = $statusCounts->total_orders ?? 0;
                $completedOrders = $statusCounts->completed_orders ?? 0;
                $pendingOrders = $statusCounts->pending_orders ?? 0;
                $processingOrders = $statusCounts->processing_orders ?? 0;
                $walkInOrders = $statusCounts->walk_in_orders ?? 0;
                $onlineOrders = $statusCounts->online_orders ?? 0;
                $totalRevenue = $statusCounts->total_revenue ?? 0.0;
                
                // Calculate average order value
                // Handle zero orders edge case
                $avgOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0.0;

                return [
                    'total_orders' => (int) $totalOrders,
                    'completed_orders' => (int) $completedOrders,
                    'pending_orders' => (int) $pendingOrders,
                    'processing_orders' => (int) $processingOrders,
                    'walk_in_orders' => (int) $walkInOrders,
                    'online_orders' => (int) $onlineOrders,
                    'avg_order_value' => (float) $avgOrderValue,
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting order metrics: ' . $e->getMessage(), [
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'total_orders' => 0,
                'completed_orders' => 0,
                'pending_orders' => 0,
                'processing_orders' => 0,
                'walk_in_orders' => 0,
                'online_orders' => 0,
                'avg_order_value' => 0.0,
            ];
        }
    }

    /**
     * Get top selling products for a given period.
     * 
     * @param int $limit Number of products to return (default: 10)
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return \Illuminate\Support\Collection Collection of products with sales data
     */
    public function getTopSellingProducts(int $limit = 10, string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): \Illuminate\Support\Collection
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics("top_products_{$limit}", $period, $endDate, function () use ($startDate, $endDate, $limit) {
                // Join order_items with orders and products
                // Filter by completed orders
                // Group by product_id
                // Sum quantities and revenue
                // Order by quantity descending
                // Limit to specified number
                $topProducts = \Illuminate\Support\Facades\DB::table('order_items')
                    ->select(
                        'order_items.product_id',
                        'products.name as product_name',
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
                    )
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('orders.order_status', 'completed')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->groupBy('order_items.product_id', 'products.name')
                    ->orderByDesc('total_quantity_sold')
                    ->limit($limit)
                    ->get();

                return $topProducts;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting top selling products: ' . $e->getMessage(), [
                'limit' => $limit,
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Get sales breakdown by category for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return \Illuminate\Support\Collection Collection of categories with sales data
     */
    public function getSalesByCategory(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): \Illuminate\Support\Collection
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('sales_by_category', $period, $endDate, function () use ($startDate, $endDate) {
                // Calculate total revenue for percentage calculation
                $totalRevenue = \App\Models\Order::where('order_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount');

                // Join orders with order_items and products
                // Group by category_id
                // Sum revenue per category
                // Order by revenue descending
                $categorySales = \App\Models\OrderItem::select(
                        'products.category_id',
                        'categories.name as category_name',
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
                    )
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('orders.order_status', 'completed')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->groupBy('products.category_id', 'categories.name')
                    ->orderByDesc('total_revenue')
                    ->get();

                // Calculate percentage of total for each category
                $categorySales = $categorySales->map(function ($category) use ($totalRevenue) {
                    $category->percentage = $totalRevenue > 0 
                        ? round(($category->total_revenue / $totalRevenue) * 100, 2)
                        : 0.0;
                    return $category;
                });

                return $categorySales;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting sales by category: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Get sales breakdown by brand for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return \Illuminate\Support\Collection Collection of brands with sales data
     */
    public function getSalesByBrand(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): \Illuminate\Support\Collection
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('sales_by_brand', $period, $endDate, function () use ($startDate, $endDate) {
                // Calculate total revenue for percentage calculation
                $totalRevenue = \App\Models\Order::where('order_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount');

                // Join orders with order_items and products
                // Group by brand_id
                // Sum revenue and units sold per brand
                // Order by revenue descending
                $brandSales = \App\Models\OrderItem::select(
                        'products.brand_id',
                        'brands.name as brand_name',
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'),
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as units_sold')
                    )
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
                    ->where('orders.order_status', 'completed')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->groupBy('products.brand_id', 'brands.name')
                    ->orderByDesc('total_revenue')
                    ->get();

                // Calculate percentage of total for each brand
                $brandSales = $brandSales->map(function ($brand) use ($totalRevenue) {
                    $brand->percentage = $totalRevenue > 0 
                        ? round(($brand->total_revenue / $totalRevenue) * 100, 2)
                        : 0.0;
                    return $brand;
                });

                return $brandSales;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting sales by brand: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Get payment method distribution for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return \Illuminate\Support\Collection Collection of payment methods with order counts and revenue
     */
    public function getPaymentMethodDistribution(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): \Illuminate\Support\Collection
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('payment_distribution', $period, $endDate, function () use ($startDate, $endDate) {
                // Calculate total orders and revenue for percentage calculation
                $totals = \App\Models\Order::where('order_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_revenue')
                    ->first();

                $totalOrders = $totals->total_orders ?? 0;
                $totalRevenue = $totals->total_revenue ?? 0;

                // Join orders with payments
                // Group by payment_method
                // Count orders and sum revenue per method
                $paymentDistribution = \App\Models\Payment::select(
                        'payments.payment_method',
                        \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                        \Illuminate\Support\Facades\DB::raw('SUM(orders.total_amount) as total_revenue')
                    )
                    ->join('orders', 'payments.order_id', '=', 'orders.id')
                    ->where('orders.order_status', 'completed')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->groupBy('payments.payment_method')
                    ->orderByDesc('total_revenue')
                    ->get();

                // Calculate percentages for each payment method
                $paymentDistribution = $paymentDistribution->map(function ($payment) use ($totalOrders, $totalRevenue) {
                    $payment->order_percentage = $totalOrders > 0 
                        ? round(($payment->order_count / $totalOrders) * 100, 2)
                        : 0.0;
                    $payment->revenue_percentage = $totalRevenue > 0 
                        ? round(($payment->total_revenue / $totalRevenue) * 100, 2)
                        : 0.0;
                    return $payment;
                });

                return $paymentDistribution;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting payment method distribution: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Compare walk-in vs online sales for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return array ['walk_in' => array, 'online' => array]
     */
    public function getChannelComparison(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('channel_comparison', $period, $endDate, function () use ($startDate, $endDate) {
                // Calculate total revenue and orders for percentage calculation
                $totals = \App\Models\Order::where('order_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_revenue')
                    ->first();

                $totalOrders = $totals->total_orders ?? 0;
                $totalRevenue = $totals->total_revenue ?? 0;

                // Group orders by order_type
                // Calculate revenue and count for walk-in vs online
                $channelData = \App\Models\Order::select(
                        'order_type',
                        \Illuminate\Support\Facades\DB::raw('COUNT(*) as order_count'),
                        \Illuminate\Support\Facades\DB::raw('SUM(total_amount) as revenue')
                    )
                    ->where('order_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('order_type')
                    ->get()
                    ->keyBy('order_type');

                // Extract walk-in data
                $walkInData = $channelData->get('walk_in');
                $walkInRevenue = $walkInData ? (float) $walkInData->revenue : 0.0;
                $walkInOrders = $walkInData ? (int) $walkInData->order_count : 0;

                // Extract online data
                $onlineData = $channelData->get('online');
                $onlineRevenue = $onlineData ? (float) $onlineData->revenue : 0.0;
                $onlineOrders = $onlineData ? (int) $onlineData->order_count : 0;

                // Calculate percentages
                $walkInRevenuePercentage = $totalRevenue > 0 
                    ? round(($walkInRevenue / $totalRevenue) * 100, 2)
                    : 0.0;
                $onlineRevenuePercentage = $totalRevenue > 0 
                    ? round(($onlineRevenue / $totalRevenue) * 100, 2)
                    : 0.0;

                $walkInOrderPercentage = $totalOrders > 0 
                    ? round(($walkInOrders / $totalOrders) * 100, 2)
                    : 0.0;
                $onlineOrderPercentage = $totalOrders > 0 
                    ? round(($onlineOrders / $totalOrders) * 100, 2)
                    : 0.0;

                return [
                    'walk_in' => [
                        'revenue' => $walkInRevenue,
                        'order_count' => $walkInOrders,
                        'revenue_percentage' => $walkInRevenuePercentage,
                        'order_percentage' => $walkInOrderPercentage,
                    ],
                    'online' => [
                        'revenue' => $onlineRevenue,
                        'order_count' => $onlineOrders,
                        'revenue_percentage' => $onlineRevenuePercentage,
                        'order_percentage' => $onlineOrderPercentage,
                    ],
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting channel comparison: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'walk_in' => [
                    'revenue' => 0.0,
                    'order_count' => 0,
                    'revenue_percentage' => 0.0,
                    'order_percentage' => 0.0,
                ],
                'online' => [
                    'revenue' => 0.0,
                    'order_count' => 0,
                    'revenue_percentage' => 0.0,
                    'order_percentage' => 0.0,
                ],
            ];
        }
    }

    /**
     * Get daily sales trend data for charts.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return array ['dates' => array, 'revenue' => array, 'orders' => array]
     */
    public function getDailySalesTrend(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        try {
            // If custom dates are provided, use them directly
            if ($customStart !== null && $customEnd !== null) {
                $startDate = $customStart;
                $endDate = $customEnd;
            } else {
                // Parse the date range from the period
                $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
                $startDate = $dateRange['start'];
                $endDate = $dateRange['end'];
            }

            return $this->cacheAnalytics('sales_trend', $period, $endDate, function () use ($period, $startDate, $endDate) {
                // Determine granularity based on period
                // For 'year' period, use monthly data points
                // For other periods, use daily data points
                $isYearly = $period === 'year';
                
                if ($isYearly) {
                    // Generate monthly data points for yearly view
                    return $this->getMonthlySalesTrend($startDate, $endDate);
                } else {
                    // Generate daily data points for other periods
                    return $this->getDailySalesTrendData($startDate, $endDate);
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting daily sales trend: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'dates' => [],
                'revenue' => [],
                'orders' => [],
            ];
        }
    }

    /**
     * Get daily sales trend data (for month, week, today periods).
     * 
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array ['dates' => array, 'revenue' => array, 'orders' => array]
     */
    protected function getDailySalesTrendData(Carbon $startDate, Carbon $endDate): array
    {
        // Query revenue and order count per date
        $salesData = \App\Models\Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as order_count')
            ->where('order_status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Generate complete date array for the period
        $dates = [];
        $revenue = [];
        $orders = [];
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M d'); // Format for display (e.g., "Nov 28")
            
            // Get data for this date or use zero if no data exists
            $dayData = $salesData->get($dateKey);
            $revenue[] = $dayData ? (float) $dayData->revenue : 0.0;
            $orders[] = $dayData ? (int) $dayData->order_count : 0;
            
            $currentDate->addDay();
        }

        return [
            'dates' => $dates,
            'revenue' => $revenue,
            'orders' => $orders,
        ];
    }

    /**
     * Get monthly sales trend data (for year period).
     * 
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array ['dates' => array, 'revenue' => array, 'orders' => array]
     */
    protected function getMonthlySalesTrend(Carbon $startDate, Carbon $endDate): array
    {
        // Query revenue and order count per month
        // Use database-agnostic date formatting
        $salesData = \App\Models\Order::selectRaw("strftime('%Y', created_at) as year, strftime('%m', created_at) as month, SUM(total_amount) as revenue, COUNT(*) as order_count")
            ->where('order_status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month;
            });

        // Generate complete month array for the period (12 months)
        $dates = [];
        $revenue = [];
        $orders = [];
        
        $currentDate = $startDate->copy()->startOfMonth();
        while ($currentDate->lte($endDate)) {
            $monthKey = $currentDate->format('Y-m');
            $dates[] = $currentDate->format('M Y'); // Format for display (e.g., "Jan 2025")
            
            // Get data for this month or use zero if no data exists
            $monthData = $salesData->get($monthKey);
            $revenue[] = $monthData ? (float) $monthData->revenue : 0.0;
            $orders[] = $monthData ? (int) $monthData->order_count : 0;
            
            $currentDate->addMonth();
        }

        return [
            'dates' => $dates,
            'revenue' => $revenue,
            'orders' => $orders,
        ];
    }

    /**
     * Get inventory alerts summary.
     * 
     * @param string|null $location Optional location filter
     * @return array [
     *   'low_stock_count' => int,
     *   'out_of_stock_count' => int,
     *   'items' => Collection
     * ]
     */
    public function getInventoryAlerts(?string $location = null): array
    {
        try {
            // Query inventory where quantity_available <= reorder_level
            // Use select to only fetch needed columns and optimize eager loading
            $query = \App\Models\Inventory::select([
                    'inventory.id',
                    'inventory.product_id',
                    'inventory.variant_id',
                    'inventory.location',
                    'inventory.quantity_available',
                    'inventory.reorder_level'
                ])
                ->with([
                    'product:id,name',  // Only select needed columns
                    'variant:id,name'   // Only select needed columns
                ])
                ->whereRaw('quantity_available <= reorder_level');

            // Apply location filter if provided
            if ($location) {
                $query->where('location', $location);
            }

            $lowStockItems = $query->get();

            // Count out of stock items (quantity_available <= 0)
            $outOfStockCount = $lowStockItems->where('quantity_available', '<=', 0)->count();

            // Calculate stock percentage for each item and add severity classification
            $items = $lowStockItems->map(function ($inventory) {
                // Calculate stock percentage (current / reorder_level * 100)
                // Handle division by zero
                $stockPercentage = $inventory->reorder_level > 0
                    ? round(($inventory->quantity_available / $inventory->reorder_level) * 100, 2)
                    : 0.0;

                // Determine severity classification
                $severity = 'normal';
                if ($inventory->quantity_available <= 0) {
                    $severity = 'out_of_stock';
                } elseif ($stockPercentage <= 25) {
                    $severity = 'critical';
                } elseif ($stockPercentage <= 50) {
                    $severity = 'warning';
                }

                return [
                    'id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'product_name' => $inventory->product->name ?? 'Unknown Product',
                    'variant_name' => $inventory->variant->name ?? null,
                    'location' => $inventory->location,
                    'quantity_available' => $inventory->quantity_available,
                    'reorder_level' => $inventory->reorder_level,
                    'stock_percentage' => $stockPercentage,
                    'is_out_of_stock' => $inventory->quantity_available <= 0,
                    'severity' => $severity,
                ];
            });

            // Order by severity (lowest stock percentage first)
            $items = $items->sortBy('stock_percentage')->values();

            return [
                'low_stock_count' => $lowStockItems->count(),
                'out_of_stock_count' => $outOfStockCount,
                'items' => $items,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting inventory alerts: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'items' => collect([]),
            ];
        }
    }

    /**
     * Get recent inventory movements.
     * 
     * @param int $limit Number of movements to return (default: 20)
     * @param string|null $location Optional location filter
     * @return \Illuminate\Support\Collection Collection of recent inventory movements
     */
    public function getRecentInventoryMovements(int $limit = 20, ?string $location = null): \Illuminate\Support\Collection
    {
        try {
            $query = \App\Models\InventoryMovement::with(['product:id,name', 'variant:id,name'])
                ->whereIn('movement_type', \App\Models\InventoryMovement::BUSINESS_MOVEMENT_TYPES)
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            // Apply location filter if provided
            if ($location) {
                $query->where(function ($q) use ($location) {
                    $q->where('location_from', $location)
                      ->orWhere('location_to', $location);
                });
            }

            return $query->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting recent inventory movements: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Get customer acquisition metrics.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return array [
     *   'total_customers' => int,
     *   'new_customers' => int,
     *   'previous_new_customers' => int,
     *   'growth_rate' => float
     * ]
     */
    public function getCustomerMetrics(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('customer_metrics', $period, $endDate, function () use ($startDate, $endDate) {
                // Count total customers (role = 'customer')
                $totalCustomers = \App\Models\User::where('role', 'customer')->count();

                // Count new customers for the selected period
                $newCustomers = \App\Models\User::where('role', 'customer')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                // Get previous period dates
                $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);

                // Count new customers in previous period
                $previousNewCustomers = \App\Models\User::where('role', 'customer')
                    ->whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
                    ->count();

                // Calculate growth rate
                $growthRate = $this->calculatePercentageChange((float) $newCustomers, (float) $previousNewCustomers);

                return [
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                    'previous_new_customers' => $previousNewCustomers,
                    'growth_rate' => $growthRate,
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting customer metrics: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'total_customers' => 0,
                'new_customers' => 0,
                'previous_new_customers' => 0,
                'growth_rate' => 0.0,
            ];
        }
    }

    /**
     * Get revenue by location for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return \Illuminate\Support\Collection Collection of locations with revenue data
     */
    public function getRevenueByLocation(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): \Illuminate\Support\Collection
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            // Join orders with order_items, products, and inventory
            // Group by inventory location
            // Sum revenue per location
            // Order by revenue descending
            $locationRevenue = \App\Models\OrderItem::select(
                    'inventory.location',
                    \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT orders.id) as order_count')
                )
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('inventory', function ($join) {
                    $join->on('products.id', '=', 'inventory.product_id')
                         ->where(function ($query) {
                             $query->whereColumn('order_items.variant_id', '=', 'inventory.variant_id')
                                   ->orWhereNull('order_items.variant_id');
                         });
                })
                ->where('orders.order_status', 'completed')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->groupBy('inventory.location')
                ->orderByDesc('total_revenue')
                ->get();

            // Format location names for display
            $locationRevenue = $locationRevenue->map(function ($location) {
                $location->location_name = ucwords(str_replace('_', ' ', $location->location));
                return $location;
            });

            return $locationRevenue;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting revenue by location: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty collection gracefully
            return collect([]);
        }
    }

    /**
     * Get profit metrics for a given period.
     * 
     * @param string $period Time period ('today'|'week'|'month'|'year'|'custom')
     * @param Carbon|null $customStart Custom start date for 'custom' period
     * @param Carbon|null $customEnd Custom end date for 'custom' period
     * @return array [
     *   'gross_profit' => float,
     *   'profit_margin' => float,
     *   'total_revenue' => float,
     *   'total_cost' => float
     * ]
     */
    public function getProfitMetrics(string $period = 'month', ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        try {
            // Parse the date range from the period
            $dateRange = $this->parseDateRange($period, $customStart, $customEnd);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            return $this->cacheAnalytics('profit_metrics', $period, $endDate, function () use ($startDate, $endDate) {
                // Query order_items with products
                // Calculate (sale_price - cost_price) * quantity
                // Filter out products with null cost_price
                $profitData = \App\Models\OrderItem::select(
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'),
                        \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * products.cost_price) as total_cost')
                    )
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('orders.order_status', 'completed')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->whereNotNull('products.cost_price')
                    ->first();

                // Extract values with defaults
                $totalRevenue = $profitData->total_revenue ?? 0.0;
                $totalCost = $profitData->total_cost ?? 0.0;

                // Calculate gross profit: revenue - cost
                $grossProfit = $totalRevenue - $totalCost;

                // Calculate profit margin percentage: (gross_profit / revenue) * 100
                // Handle division by zero
                $profitMargin = $totalRevenue > 0 
                    ? round(($grossProfit / $totalRevenue) * 100, 2)
                    : 0.0;

                return [
                    'gross_profit' => (float) $grossProfit,
                    'profit_margin' => $profitMargin,
                    'total_revenue' => (float) $totalRevenue,
                    'total_cost' => (float) $totalCost,
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting profit metrics: ' . $e->getMessage(), [
                'period' => $period,
                'exception' => $e
            ]);
            
            // Return empty/default values gracefully
            return [
                'gross_profit' => 0.0,
                'profit_margin' => 0.0,
                'total_revenue' => 0.0,
                'total_cost' => 0.0,
            ];
        }
    }
}
