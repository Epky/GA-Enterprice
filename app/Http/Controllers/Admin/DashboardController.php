<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin Dashboard Controller
 * 
 * Handles the admin dashboard display and analytics data management.
 * Provides comprehensive business intelligence through various metrics
 * including revenue, orders, products, customers, and inventory.
 * 
 * @package App\Http\Controllers\Admin
 */
class DashboardController extends Controller
{
    /**
     * The analytics service instance.
     *
     * @var AnalyticsService
     */
    protected AnalyticsService $analyticsService;

    /**
     * Create a new controller instance.
     *
     * @param AnalyticsService $analyticsService The analytics service for data aggregation
     * @return void
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the admin dashboard with analytics.
     * 
     * Loads comprehensive analytics data including revenue metrics, order statistics,
     * top products, category/brand breakdowns, payment distributions, and more.
     * Supports multiple time periods (today, week, month, year, custom).
     * 
     * @param Request $request The HTTP request with optional period and date filters
     * @return \Illuminate\View\View The admin dashboard view with analytics data
     * 
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * 
     * @example
     * // View dashboard for current month (default)
     * GET /admin/dashboard
     * 
     * // View dashboard for this week
     * GET /admin/dashboard?period=week
     * 
     * // View dashboard for custom date range
     * GET /admin/dashboard?period=custom&start_date=2024-01-01&end_date=2024-01-31
     */
    public function index(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Get time period from request (default to 'month')
            $period = $validated['period'] ?? 'month';
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;
            
            // Get dashboard statistics
            $stats = $this->getDashboardStats();
            
            // Get recent activities
            $recentUsers = $this->getRecentUsers();
            
            // Get system health data
            $systemHealth = $this->getSystemHealth();
            
            // Get date range for period
            $dateRange = $this->getDateRangeForPeriod($period, $startDate, $endDate);
            
            // Get analytics data
            $analytics = [
                'revenue' => $this->analyticsService->calculateRevenue($dateRange['start'], $dateRange['end']),
                'order_metrics' => $this->analyticsService->getOrderMetrics($dateRange['start'], $dateRange['end']),
                'top_products' => $this->analyticsService->getTopSellingProducts(10, $period),
                'sales_by_category' => $this->analyticsService->getSalesByCategory($period),
                'sales_by_brand' => $this->analyticsService->getSalesByBrand($period),
                'payment_distribution' => $this->analyticsService->getPaymentMethodDistribution($period),
                'channel_comparison' => $this->analyticsService->getChannelComparison($period),
                'profit_metrics' => $this->analyticsService->getProfitMetrics($period),
                'sales_trend' => $this->analyticsService->getDailySalesTrend($period),
                'customer_metrics' => $this->analyticsService->getCustomerMetrics($period),
                'inventory_alerts' => $this->analyticsService->getInventoryAlerts(),
                'revenue_by_location' => $this->analyticsService->getRevenueByLocation($period),
            ];
            
            return view('admin.dashboard', compact('stats', 'recentUsers', 'systemHealth', 'analytics', 'period'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show validation errors
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error loading admin dashboard: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'period' => $request->input('period', 'month'),
                'user_id' => Auth::id()
            ]);
            
            // Try to get cached analytics data as fallback
            $cachedAnalytics = $this->getCachedAnalytics($period);
            
            // Return view with error message and cached data if available
            return view('admin.dashboard', [
                'stats' => $this->getDashboardStats(),
                'recentUsers' => $this->getRecentUsers(),
                'systemHealth' => $this->getSystemHealth(),
                'analytics' => $cachedAnalytics,
                'period' => $request->input('period', 'month'),
                'error' => $cachedAnalytics 
                    ? 'Unable to load fresh analytics data. Showing cached data from an earlier time.'
                    : 'Unable to load analytics data. Please try again later.'
            ]);
        }
    }
    
    /**
     * Get basic dashboard statistics.
     * 
     * Retrieves user counts by role and registration periods.
     * 
     * @return array Dashboard statistics including user counts
     */
    private function getDashboardStats()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'staff_users' => User::where('role', 'staff')->count(),
            'customer_users' => User::where('role', 'customer')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];
    }
    
    /**
     * Get recently registered users.
     * 
     * Retrieves the 10 most recently created user accounts.
     * 
     * @return \Illuminate\Database\Eloquent\Collection Collection of recent users
     */
    private function getRecentUsers()
    {
        return User::latest()
            ->take(10)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);
    }
    
    /**
     * Get system health information.
     * 
     * Checks database connectivity and retrieves system version information.
     * 
     * @return array System health metrics including database status and versions
     */
    private function getSystemHealth()
    {
        return [
            'database_status' => $this->checkDatabaseConnection(),
            'total_tables' => $this->getTotalTables(),
            'app_version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
    
    /**
     * Check database connection status.
     * 
     * Attempts to connect to the database and returns connection status.
     * 
     * @return string 'Connected' or 'Disconnected'
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return 'Connected';
        } catch (\Exception $e) {
            return 'Disconnected';
        }
    }
    
    /**
     * Get total number of database tables.
     * 
     * Queries the information schema to count tables in the current database.
     * 
     * @return int Number of tables in the database
     */
    private function getTotalTables()
    {
        try {
            $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.supabase.database')]);
            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Export analytics data to CSV.
     * 
     * Generates a comprehensive CSV export of all analytics data for the selected
     * time period. The export includes revenue metrics, order statistics, top products,
     * category/brand breakdowns, payment distributions, channel comparisons, profit
     * metrics, customer metrics, and revenue by location.
     * 
     * @param Request $request The HTTP request with period and date filters
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSV file download
     * 
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * 
     * @example
     * // Export analytics for current month
     * GET /admin/analytics/export?period=month
     * 
     * // Export analytics for custom date range
     * GET /admin/analytics/export?period=custom&start_date=2024-01-01&end_date=2024-01-31
     */
    public function exportAnalytics(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Get time period from request (default to 'month')
            $period = $validated['period'] ?? 'month';
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;
            
            // Get date range for period
            $dateRange = $this->getDateRangeForPeriod($period, $startDate, $endDate);
            
            // Get analytics data for selected period
            $revenue = $this->analyticsService->calculateRevenue($dateRange['start'], $dateRange['end']);
            $orderMetrics = $this->analyticsService->getOrderMetrics($dateRange['start'], $dateRange['end']);
            $topProducts = $this->analyticsService->getTopSellingProducts(10, $period);
            $salesByCategory = $this->analyticsService->getSalesByCategory($period);
            $salesByBrand = $this->analyticsService->getSalesByBrand($period);
            $paymentDistribution = $this->analyticsService->getPaymentMethodDistribution($period);
            $channelComparison = $this->analyticsService->getChannelComparison($period);
            $profitMetrics = $this->analyticsService->getProfitMetrics($period);
            $customerMetrics = $this->analyticsService->getCustomerMetrics($period);
            $revenueByLocation = $this->analyticsService->getRevenueByLocation($period);
            
            // Get date range for filename
            $filename = 'analytics_' . $dateRange['start']->format('Y-m-d') . '_to_' . $dateRange['end']->format('Y-m-d') . '.csv';
            
            // Generate CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($revenue, $orderMetrics, $topProducts, $salesByCategory, $salesByBrand, $paymentDistribution, $channelComparison, $profitMetrics, $customerMetrics, $revenueByLocation, $period) {
                $file = fopen('php://output', 'w');
                
                // Summary Section
                fputcsv($file, ['Analytics Summary']);
                fputcsv($file, ['Period', $period]);
                fputcsv($file, []);
                
                // Revenue Metrics
                fputcsv($file, ['Revenue Metrics']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Revenue', $revenue['total'] ?? 0]);
                fputcsv($file, ['Previous Period Revenue', $revenue['previous_total'] ?? 0]);
                fputcsv($file, ['Change Percentage', ($revenue['change_percent'] ?? 0) . '%']);
                fputcsv($file, []);
                
                // Order Metrics
                fputcsv($file, ['Order Metrics']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Orders', $orderMetrics['total_orders'] ?? 0]);
                fputcsv($file, ['Completed Orders', $orderMetrics['completed_orders'] ?? 0]);
                fputcsv($file, ['Walk-in Orders', $orderMetrics['walk_in_orders'] ?? 0]);
                fputcsv($file, ['Online Orders', $orderMetrics['online_orders'] ?? 0]);
                fputcsv($file, ['Average Order Value', $orderMetrics['avg_order_value'] ?? 0]);
                fputcsv($file, []);
                
                // Profit Metrics
                fputcsv($file, ['Profit Metrics']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Gross Profit', $profitMetrics['gross_profit'] ?? 0]);
                fputcsv($file, ['Profit Margin', ($profitMetrics['profit_margin'] ?? 0) . '%']);
                fputcsv($file, ['Total Cost', $profitMetrics['total_cost'] ?? 0]);
                fputcsv($file, []);
                
                // Customer Metrics
                fputcsv($file, ['Customer Metrics']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Customers', $customerMetrics['total_customers'] ?? 0]);
                fputcsv($file, ['New Customers', $customerMetrics['new_customers'] ?? 0]);
                fputcsv($file, ['Growth Rate', ($customerMetrics['growth_rate'] ?? 0) . '%']);
                fputcsv($file, []);
                
                // Top Products
                fputcsv($file, ['Top Selling Products']);
                fputcsv($file, ['Product Name', 'Quantity Sold', 'Revenue']);
                foreach ($topProducts as $product) {
                    fputcsv($file, [
                        $product->product_name ?? 'Unknown',
                        $product->total_quantity ?? 0,
                        $product->total_revenue ?? 0
                    ]);
                }
                fputcsv($file, []);
                
                // Sales by Category
                fputcsv($file, ['Sales by Category']);
                fputcsv($file, ['Category', 'Revenue', 'Percentage']);
                foreach ($salesByCategory as $category) {
                    fputcsv($file, [
                        $category->category_name ?? 'Unknown',
                        $category->total_revenue ?? 0,
                        ($category->percentage ?? 0) . '%'
                    ]);
                }
                fputcsv($file, []);
                
                // Sales by Brand
                fputcsv($file, ['Sales by Brand']);
                fputcsv($file, ['Brand', 'Revenue', 'Units Sold']);
                foreach ($salesByBrand as $brand) {
                    fputcsv($file, [
                        $brand->brand_name ?? 'Unknown',
                        $brand->total_revenue ?? 0,
                        $brand->units_sold ?? 0
                    ]);
                }
                fputcsv($file, []);
                
                // Payment Method Distribution
                fputcsv($file, ['Payment Method Distribution']);
                fputcsv($file, ['Payment Method', 'Order Count', 'Revenue', 'Percentage']);
                foreach ($paymentDistribution as $payment) {
                    fputcsv($file, [
                        $payment->payment_method ?? 'Unknown',
                        $payment->order_count ?? 0,
                        $payment->total_revenue ?? 0,
                        ($payment->percentage ?? 0) . '%'
                    ]);
                }
                fputcsv($file, []);
                
                // Channel Comparison
                fputcsv($file, ['Channel Comparison']);
                fputcsv($file, ['Channel', 'Revenue', 'Order Count', 'Percentage']);
                fputcsv($file, [
                    'Walk-in',
                    $channelComparison['walk_in']['revenue'] ?? 0,
                    $channelComparison['walk_in']['order_count'] ?? 0,
                    ($channelComparison['walk_in']['percentage'] ?? 0) . '%'
                ]);
                fputcsv($file, [
                    'Online',
                    $channelComparison['online']['revenue'] ?? 0,
                    $channelComparison['online']['order_count'] ?? 0,
                    ($channelComparison['online']['percentage'] ?? 0) . '%'
                ]);
                fputcsv($file, []);
                
                // Revenue by Location
                fputcsv($file, ['Revenue by Location']);
                fputcsv($file, ['Location', 'Revenue', 'Order Count']);
                foreach ($revenueByLocation as $location) {
                    fputcsv($file, [
                        $location->location_name ?? 'Unknown',
                        $location->total_revenue ?? 0,
                        $location->order_count ?? 0
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show validation errors
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Error exporting analytics: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Unable to export analytics data. Please try again later.');
        }
    }

    /**
     * Display the Sales & Revenue page.
     * 
     * Shows detailed sales and revenue analytics including revenue metrics,
     * order statistics, sales trends, top products, and category/brand breakdowns.
     * 
     * @param Request $request The HTTP request with optional period and date filters
     * @return \Illuminate\View\View The sales & revenue view with analytics data
     */
    public function salesRevenue(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Get time period from request (default to 'month')
            $period = $validated['period'] ?? 'month';
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;
            
            // Get date range for period
            $dateRange = $this->getDateRangeForPeriod($period, $startDate, $endDate);
            
            // Get sales and revenue analytics data
            $analytics = [
                'revenue' => $this->analyticsService->calculateRevenue($dateRange['start'], $dateRange['end']),
                'order_metrics' => $this->analyticsService->getOrderMetrics($dateRange['start'], $dateRange['end']),
                'profit_metrics' => $this->analyticsService->getProfitMetrics($period),
                'sales_trend' => $this->analyticsService->getDailySalesTrend($period),
                'top_products' => $this->analyticsService->getTopSellingProducts(10, $period),
                'category_breakdown' => $this->analyticsService->getSalesByCategory($period),
                'brand_breakdown' => $this->analyticsService->getSalesByBrand($period),
            ];
            
            return view('admin.sales-revenue', compact('analytics', 'period'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error loading sales & revenue page: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'period' => $request->input('period', 'month'),
                'user_id' => Auth::id()
            ]);
            
            // Try to get cached analytics data as fallback
            $cachedAnalytics = $this->getCachedSalesAnalytics($period);
            
            return view('admin.sales-revenue', [
                'analytics' => $cachedAnalytics,
                'period' => $request->input('period', 'month'),
                'error' => $cachedAnalytics 
                    ? 'Unable to load fresh sales data. Showing cached data from an earlier time.'
                    : 'Unable to load sales and revenue data. Please try again later.'
            ]);
        }
    }

    /**
     * Display the Customers & Channels page.
     * 
     * Shows customer analytics and sales channel comparisons including customer metrics,
     * channel comparison, payment method distribution, and customer acquisition trends.
     * 
     * @param Request $request The HTTP request with optional period and date filters
     * @return \Illuminate\View\View The customers & channels view with analytics data
     */
    public function customersChannels(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Get time period from request (default to 'month')
            $period = $validated['period'] ?? 'month';
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;
            
            // Get customers and channels analytics data
            $analytics = [
                'customer_metrics' => $this->analyticsService->getCustomerMetrics($period),
                'channel_comparison' => $this->analyticsService->getChannelComparison($period),
                'payment_distribution' => $this->analyticsService->getPaymentMethodDistribution($period),
            ];
            
            return view('admin.customers-channels', compact('analytics', 'period'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error loading customers & channels page: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'period' => $request->input('period', 'month'),
                'user_id' => Auth::id()
            ]);
            
            // Try to get cached analytics data as fallback
            $cachedAnalytics = $this->getCachedCustomerAnalytics($period);
            
            return view('admin.customers-channels', [
                'analytics' => $cachedAnalytics,
                'period' => $request->input('period', 'month'),
                'error' => $cachedAnalytics 
                    ? 'Unable to load fresh customer data. Showing cached data from an earlier time.'
                    : 'Unable to load customer and channel data. Please try again later.'
            ]);
        }
    }

    /**
     * Display the Inventory Insights page.
     * 
     * Shows inventory analytics including low stock alerts, inventory movements,
     * and revenue by location.
     * 
     * @param Request $request The HTTP request with optional period and location filters
     * @return \Illuminate\View\View The inventory insights view with analytics data
     */
    public function inventoryInsights(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
                'location' => 'nullable|string',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Get time period from request (default to 'month')
            $period = $validated['period'] ?? 'month';
            $location = $validated['location'] ?? null;
            
            // Get inventory analytics data
            $analytics = [
                'inventory_alerts' => $this->analyticsService->getInventoryAlerts($location),
                'recent_movements' => $this->analyticsService->getRecentInventoryMovements(20, $location),
                'revenue_by_location' => $this->analyticsService->getRevenueByLocation($period),
            ];
            
            return view('admin.inventory-insights', compact('analytics', 'period', 'location'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error loading inventory insights page: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'period' => $request->input('period', 'month'),
                'location' => $request->input('location'),
                'user_id' => Auth::id()
            ]);
            
            // Try to get cached analytics data as fallback
            $cachedAnalytics = $this->getCachedInventoryAnalytics($period, $location);
            
            return view('admin.inventory-insights', [
                'analytics' => $cachedAnalytics,
                'period' => $request->input('period', 'month'),
                'location' => $request->input('location'),
                'error' => $cachedAnalytics 
                    ? 'Unable to load fresh inventory data. Showing cached data from an earlier time.'
                    : 'Unable to load inventory data. Please try again later.'
            ]);
        }
    }

    /**
     * Get date range for a given period.
     * 
     * Converts a period string (today, week, month, year, custom) into
     * Carbon date objects representing the start and end of that period.
     * 
     * @param string $period The period identifier ('today'|'week'|'month'|'year'|'custom')
     * @param string|null $startDate Custom start date (required for 'custom' period)
     * @param string|null $endDate Custom end date (required for 'custom' period)
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    private function getDateRangeForPeriod(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return [
                'start' => \Carbon\Carbon::parse($startDate)->startOfDay(),
                'end' => \Carbon\Carbon::parse($endDate)->endOfDay(),
            ];
        }
        
        $end = now();
        
        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                break;
            case 'week':
                $start = now()->startOfWeek();
                break;
            case 'year':
                $start = now()->startOfYear();
                break;
            case 'month':
            default:
                $start = now()->startOfMonth();
                break;
        }
        
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get analytics data via AJAX for dynamic updates.
     * 
     * Returns analytics data as JSON for AJAX requests. This endpoint allows
     * the dashboard to dynamically update analytics without a full page reload
     * when the user changes the time period filter.
     * 
     * @param Request $request The HTTP request with period and date filters
     * @return \Illuminate\Http\JsonResponse JSON response with analytics data or error
     * 
     * @example
     * // Fetch analytics for this week via AJAX
     * GET /admin/analytics/data?period=week
     * 
     * Response format:
     * {
     *   "success": true,
     *   "data": {
     *     "revenue": {...},
     *     "order_metrics": {...},
     *     "top_products": [...],
     *     ...
     *   },
     *   "period": "week"
     * }
     */
    public function getAnalyticsData(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'period' => 'nullable|in:today,week,month,year,custom',
                'start_date' => 'nullable|required_if:period,custom|date',
                'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
            ], [
                'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
                'start_date.required_if' => 'The start date is required when using a custom period.',
                'end_date.required_if' => 'The end date is required when using a custom period.',
            ]);
            
            // Accept period parameter
            $period = $validated['period'] ?? 'month';
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;
            
            // Get date range for period
            $dateRange = $this->getDateRangeForPeriod($period, $startDate, $endDate);
            
            // Get analytics data
            $analytics = [
                'revenue' => $this->analyticsService->calculateRevenue($dateRange['start'], $dateRange['end']),
                'order_metrics' => $this->analyticsService->getOrderMetrics($dateRange['start'], $dateRange['end']),
                'top_products' => $this->analyticsService->getTopSellingProducts(10, $period),
                'sales_by_category' => $this->analyticsService->getSalesByCategory($period),
                'sales_by_brand' => $this->analyticsService->getSalesByBrand($period),
                'payment_distribution' => $this->analyticsService->getPaymentMethodDistribution($period),
                'channel_comparison' => $this->analyticsService->getChannelComparison($period),
                'profit_metrics' => $this->analyticsService->getProfitMetrics($period),
                'sales_trend' => $this->analyticsService->getDailySalesTrend($period),
                'customer_metrics' => $this->analyticsService->getCustomerMetrics($period),
                'inventory_alerts' => $this->analyticsService->getInventoryAlerts(),
                'revenue_by_location' => $this->analyticsService->getRevenueByLocation($period),
            ];
            
            // Return JSON response with analytics
            return response()->json([
                'success' => true,
                'data' => $analytics,
                'period' => $period
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors with proper JSON error format
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ]
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching analytics data via AJAX: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Handle errors with proper JSON error format
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Unable to load analytics data. Please try again later.',
                    'code' => 'ANALYTICS_ERROR',
                    'details' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request'
                ]
            ], 500);
        }
    }

    /**
     * Get cached analytics data as fallback.
     * 
     * Attempts to retrieve cached analytics data when fresh data cannot be loaded.
     * This provides a better user experience by showing stale data rather than no data.
     * 
     * @param string $period The time period for analytics
     * @return array|null Cached analytics data or null if not available
     */
    private function getCachedAnalytics(string $period): ?array
    {
        try {
            $cacheKey = "dashboard_analytics_{$period}";
            
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info('Using cached analytics data as fallback', ['period' => $period]);
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error retrieving cached analytics: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get cached sales analytics data as fallback.
     * 
     * @param string $period The time period for analytics
     * @return array|null Cached sales analytics data or null if not available
     */
    private function getCachedSalesAnalytics(string $period): ?array
    {
        try {
            $cacheKey = "sales_analytics_{$period}";
            
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info('Using cached sales analytics data as fallback', ['period' => $period]);
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error retrieving cached sales analytics: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get cached customer analytics data as fallback.
     * 
     * @param string $period The time period for analytics
     * @return array|null Cached customer analytics data or null if not available
     */
    private function getCachedCustomerAnalytics(string $period): ?array
    {
        try {
            $cacheKey = "customer_analytics_{$period}";
            
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info('Using cached customer analytics data as fallback', ['period' => $period]);
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error retrieving cached customer analytics: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get cached inventory analytics data as fallback.
     * 
     * @param string $period The time period for analytics
     * @param string|null $location Optional location filter
     * @return array|null Cached inventory analytics data or null if not available
     */
    private function getCachedInventoryAnalytics(string $period, ?string $location = null): ?array
    {
        try {
            $cacheKey = "inventory_analytics_{$period}" . ($location ? "_{$location}" : '');
            
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info('Using cached inventory analytics data as fallback', [
                    'period' => $period,
                    'location' => $location
                ]);
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error retrieving cached inventory analytics: ' . $e->getMessage());
            return null;
        }
    }
}
