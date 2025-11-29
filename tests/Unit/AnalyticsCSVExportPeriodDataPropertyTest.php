<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Feature: admin-dashboard-reorganization, Property 6: CSV export includes period data
 * 
 * Property: For any time period selection, the exported CSV file should contain 
 * only data from the selected period and include all relevant metrics
 * 
 * Validates: Requirements 2.6
 */
class AnalyticsCSVExportPeriodDataPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analyticsService;
    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
        $this->controller = new DashboardController($this->analyticsService);
    }

    /**
     * Test that CSV export includes only data from selected period
     * 
     * @test
     */
    public function test_csv_export_includes_period_data()
    {
        // Run the property test with random data
        $this->runPropertyTest();
    }

    private function runPropertyTest()
    {
        // Generate random test data
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create categories, brands, and products
        $categories = Category::factory()->count(3)->create();
        $brands = Brand::factory()->count(3)->create();
        
        $products = [];
        for ($i = 0; $i < 5; $i++) {
            $basePrice = rand(200, 1000);
            $product = Product::factory()->create([
                'category_id' => $categories->random()->id,
                'brand_id' => $brands->random()->id,
                'cost_price' => rand(100, 500),
                'base_price' => $basePrice,
                'sale_price' => rand(0, 1) ? null : rand($basePrice * 0.7, $basePrice * 0.9),
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => rand(0, 100),
                'reorder_level' => rand(5, 20),
            ]);
            
            $products[] = $product;
        }

        // Create orders in different time periods
        $ordersInPeriod = [];
        $ordersOutsidePeriod = [];
        
        // Choose a random period
        $periods = ['today', 'week', 'month'];
        $period = $periods[array_rand($periods)];
        
        // Determine date range for the period
        $dateRange = $this->getDateRangeForPeriod($period);
        
        // Create orders within the period
        for ($i = 0; $i < rand(3, 8); $i++) {
            $orderDate = $this->randomDateInRange($dateRange['start'], $dateRange['end']);
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'order_type' => rand(0, 1) ? 'walk_in' : 'online',
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            Payment::factory()->create([
                'order_id' => $order->id,
                'payment_method' => ['cash', 'credit_card', 'debit_card', 'gcash'][rand(0, 3)],
                'amount' => $order->total_amount,
                'created_at' => $orderDate,
            ]);

            // Add order items
            $itemCount = rand(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 5);
                $unitPrice = $product->sale_price ?? $product->base_price;
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'created_at' => $orderDate,
                ]);
            }
            
            $ordersInPeriod[] = $order;
        }
        
        // Create orders outside the period
        for ($i = 0; $i < rand(2, 5); $i++) {
            $orderDate = $dateRange['start']->copy()->subDays(rand(10, 60));
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'order_type' => rand(0, 1) ? 'walk_in' : 'online',
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            Payment::factory()->create([
                'order_id' => $order->id,
                'payment_method' => ['cash', 'credit_card', 'debit_card', 'gcash'][rand(0, 3)],
                'amount' => $order->total_amount,
                'created_at' => $orderDate,
            ]);

            // Add order items
            $itemCount = rand(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 5);
                $unitPrice = $product->sale_price ?? $product->base_price;
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'created_at' => $orderDate,
                ]);
            }
            
            $ordersOutsidePeriod[] = $order;
        }

        // Calculate expected metrics for the period
        $expectedRevenue = $this->analyticsService->calculateRevenue($dateRange['start'], $dateRange['end']);
        $expectedOrderMetrics = $this->analyticsService->getOrderMetrics($dateRange['start'], $dateRange['end']);

        // Export CSV for the period
        $request = Request::create('/admin/analytics/export', 'GET', ['period' => $period]);
        $response = $this->controller->exportAnalytics($request);

        // Get CSV content
        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        // Parse CSV content
        $lines = array_filter(explode("\n", $csvContent), function($line) {
            return trim($line) !== '';
        });

        // Property 1: CSV should include the period information
        $periodFound = false;
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            if (isset($columns[0]) && trim($columns[0]) === 'Period') {
                $this->assertEquals($period, trim($columns[1]), 'CSV should include the correct period');
                $periodFound = true;
                break;
            }
        }
        $this->assertTrue($periodFound, 'CSV should contain period information');

        // Property 2: CSV revenue should match calculated revenue for the period
        $revenueFound = false;
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            if (isset($columns[0]) && trim($columns[0]) === 'Total Revenue') {
                $csvRevenue = floatval(trim($columns[1]));
                $expectedRevenueValue = floatval($expectedRevenue['total'] ?? 0);
                $this->assertEquals(
                    $expectedRevenueValue,
                    $csvRevenue,
                    "CSV revenue ({$csvRevenue}) should match calculated revenue ({$expectedRevenueValue}) for period {$period}",
                    0.01
                );
                $revenueFound = true;
                break;
            }
        }
        $this->assertTrue($revenueFound, 'CSV should contain revenue data');

        // Property 3: CSV order count should match calculated order count for the period
        $orderCountFound = false;
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            if (isset($columns[0]) && trim($columns[0]) === 'Total Orders') {
                $csvOrderCount = intval(trim($columns[1]));
                $expectedOrderCount = intval($expectedOrderMetrics['total_orders'] ?? 0);
                $this->assertEquals(
                    $expectedOrderCount,
                    $csvOrderCount,
                    "CSV order count ({$csvOrderCount}) should match calculated order count ({$expectedOrderCount}) for period {$period}"
                );
                $orderCountFound = true;
                break;
            }
        }
        $this->assertTrue($orderCountFound, 'CSV should contain order count data');

        // Property 4: CSV should include all required metric sections
        $requiredSections = [
            'Analytics Summary',
            'Revenue Metrics',
            'Order Metrics',
            'Profit Metrics',
            'Customer Metrics',
            'Top Selling Products',
            'Sales by Category',
            'Sales by Brand',
            'Payment Method Distribution',
            'Channel Comparison',
            'Revenue by Location'
        ];

        foreach ($requiredSections as $section) {
            $sectionFound = false;
            foreach ($lines as $line) {
                $columns = str_getcsv($line);
                if (isset($columns[0]) && trim($columns[0]) === $section) {
                    $sectionFound = true;
                    break;
                }
            }
            $this->assertTrue($sectionFound, "CSV should contain '{$section}' section");
        }
    }

    /**
     * Get date range for a given period
     */
    private function getDateRangeForPeriod(string $period): array
    {
        $end = now();
        
        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                break;
            case 'week':
                $start = now()->startOfWeek();
                break;
            case 'month':
            default:
                $start = now()->startOfMonth();
                break;
        }
        
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Generate a random date within a range
     */
    private function randomDateInRange($start, $end)
    {
        $timestamp = rand($start->timestamp, $end->timestamp);
        return \Carbon\Carbon::createFromTimestamp($timestamp);
    }
}
