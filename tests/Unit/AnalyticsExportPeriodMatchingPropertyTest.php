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
 * Feature: admin-dashboard-reorganization, Property 18: Export respects current period
 * 
 * Property: For any time period selection, the exported CSV data should only include 
 * records that fall within the selected period's date range
 * 
 * Validates: Requirements 6.4
 */
class AnalyticsExportPeriodMatchingPropertyTest extends TestCase
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
     * Test that export respects the current period selection
     * 
     * @test
     */
    public function test_export_respects_current_period()
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
        $categories = Category::factory()->count(2)->create();
        $brands = Brand::factory()->count(2)->create();
        
        $products = [];
        for ($i = 0; $i < 3; $i++) {
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

        // Choose a random period
        $periods = ['today', 'week', 'month'];
        $period = $periods[array_rand($periods)];
        
        // Determine date range for the period
        $dateRange = $this->getDateRangeForPeriod($period);
        
        // Create orders within the period
        $ordersInPeriod = [];
        for ($i = 0; $i < rand(2, 5); $i++) {
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
            $itemCount = rand(1, 2);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 3);
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
            
            $ordersInPeriod[] = $order->id;
        }
        
        // Create orders outside the period (before the period)
        $ordersOutsidePeriod = [];
        for ($i = 0; $i < rand(2, 4); $i++) {
            $orderDate = $dateRange['start']->copy()->subDays(rand(10, 30));
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
            $itemCount = rand(1, 2);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $quantity = rand(1, 3);
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
            
            $ordersOutsidePeriod[] = $order->id;
        }

        // Calculate expected metrics for the period (only orders in period)
        $expectedOrderCount = count($ordersInPeriod);
        $expectedRevenue = Order::whereIn('id', $ordersInPeriod)
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');

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

        // Property 1: CSV should only include data from the selected period
        // Verify that the order count in CSV matches only orders within the period
        $csvOrderCount = null;
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            if (isset($columns[0]) && trim($columns[0]) === 'Total Orders') {
                $csvOrderCount = intval(trim($columns[1]));
                break;
            }
        }
        
        $this->assertNotNull($csvOrderCount, 'CSV should contain Total Orders metric');
        $this->assertEquals(
            $expectedOrderCount,
            $csvOrderCount,
            "CSV order count ({$csvOrderCount}) should match orders in period ({$expectedOrderCount}), not include orders outside period"
        );

        // Property 2: CSV revenue should only include revenue from the selected period
        $csvRevenue = null;
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            if (isset($columns[0]) && trim($columns[0]) === 'Total Revenue') {
                $csvRevenue = floatval(trim($columns[1]));
                break;
            }
        }
        
        $this->assertNotNull($csvRevenue, 'CSV should contain Total Revenue metric');
        $this->assertEquals(
            $expectedRevenue,
            $csvRevenue,
            "CSV revenue ({$csvRevenue}) should match revenue in period ({$expectedRevenue}), not include revenue outside period",
            0.01
        );

        // Property 3: Verify that the date range in filename matches the period
        $headers = $response->headers->all();
        $contentDisposition = $headers['content-disposition'][0] ?? '';
        
        $this->assertStringContainsString('analytics_', $contentDisposition, 'Filename should contain analytics prefix');
        $this->assertStringContainsString('.csv', $contentDisposition, 'Filename should have .csv extension');
        
        // Extract dates from filename
        preg_match('/analytics_(\d{4}-\d{2}-\d{2})_to_(\d{4}-\d{2}-\d{2})\.csv/', $contentDisposition, $matches);
        
        if (count($matches) === 3) {
            $filenameStartDate = \Carbon\Carbon::parse($matches[1]);
            $filenameEndDate = \Carbon\Carbon::parse($matches[2]);
            
            // Verify filename dates match the period's date range
            $this->assertEquals(
                $dateRange['start']->format('Y-m-d'),
                $filenameStartDate->format('Y-m-d'),
                'Filename start date should match period start date'
            );
            
            $this->assertEquals(
                $dateRange['end']->format('Y-m-d'),
                $filenameEndDate->format('Y-m-d'),
                'Filename end date should match period end date'
            );
        }

        // Property 4: Verify that orders outside the period are NOT included in top products
        $topProductsSection = $this->extractSection($lines, 'Top Selling Products');
        if (!empty($topProductsSection)) {
            // Get all product IDs from orders in period
            $productIdsInPeriod = OrderItem::whereIn('order_id', $ordersInPeriod)->pluck('product_id')->unique()->toArray();
            
            // Skip header row
            array_shift($topProductsSection);
            
            // Verify all products in top products are from orders in the period
            foreach ($topProductsSection as $row) {
                $columns = str_getcsv($row);
                if (isset($columns[0]) && !empty(trim($columns[0]))) {
                    $productName = trim($columns[0]);
                    $product = Product::where('name', $productName)->first();
                    
                    if ($product) {
                        $this->assertContains(
                            $product->id,
                            $productIdsInPeriod,
                            "Product '{$productName}' in CSV should only be from orders within the selected period"
                        );
                    }
                }
            }
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

    /**
     * Extract a section from CSV lines
     */
    private function extractSection(array $lines, string $sectionName): array
    {
        $section = [];
        $inSection = false;
        
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            
            // Check if this is the section header
            if (isset($columns[0]) && trim($columns[0]) === $sectionName) {
                $inSection = true;
                continue;
            }
            
            // If we're in the section, collect rows until we hit an empty row
            if ($inSection) {
                if (empty(trim($line)) || (isset($columns[0]) && empty(trim($columns[0])))) {
                    break;
                }
                $section[] = $line;
            }
        }
        
        return $section;
    }
}
