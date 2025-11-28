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
 * Feature: admin-analytics-dashboard, Property 15: CSV export data integrity
 * 
 * Property: For any exported CSV file, the number of data rows should match 
 * the number of items in the source data, and each row should have the same 
 * number of columns as the header row
 * 
 * Validates: Requirements 12.2, 12.3, 12.4
 */
class AnalyticsCSVExportDataIntegrityPropertyTest extends TestCase
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
     * Test that CSV export maintains data integrity
     * 
     * @test
     */
    public function test_csv_export_data_integrity_with_random_data()
    {
        // Run the property test with random data
        $this->runPropertyTest();
    }

    private function runPropertyTest()
    {
        // Generate random test data
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create random number of categories, brands, and products
        $categoryCount = rand(1, 5);
        $brandCount = rand(1, 5);
        $productCount = rand(3, 10);

        $categories = Category::factory()->count($categoryCount)->create();
        $brands = Brand::factory()->count($brandCount)->create();
        
        $products = [];
        for ($i = 0; $i < $productCount; $i++) {
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

        // Create random number of completed orders
        $orderCount = rand(5, 15);
        for ($i = 0; $i < $orderCount; $i++) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'order_type' => rand(0, 1) ? 'walk_in' : 'online',
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            Payment::factory()->create([
                'order_id' => $order->id,
                'payment_method' => ['cash', 'credit_card', 'debit_card', 'gcash'][rand(0, 3)],
                'amount' => $order->total_amount,
            ]);

            // Add random order items
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
                ]);
            }
        }

        // Get analytics data to compare
        $period = 'month';
        $topProducts = $this->analyticsService->getTopSellingProducts(10, $period);
        $salesByCategory = $this->analyticsService->getSalesByCategory($period);
        $salesByBrand = $this->analyticsService->getSalesByBrand($period);
        $paymentDistribution = $this->analyticsService->getPaymentMethodDistribution($period);
        $revenueByLocation = $this->analyticsService->getRevenueByLocation($period);

        // Create request and export CSV
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

        // Property 1: CSV should be valid format
        $this->assertNotEmpty($csvContent, 'CSV content should not be empty');
        $this->assertGreaterThan(0, count($lines), 'CSV should have at least one line');

        // Property 2: Verify Top Products section data integrity
        $topProductsSection = $this->extractSection($lines, 'Top Selling Products');
        if (!empty($topProductsSection)) {
            $headerRow = array_shift($topProductsSection); // Remove header
            $headerColumns = str_getcsv($headerRow);
            
            // Each data row should have same number of columns as header
            foreach ($topProductsSection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Top Products should have {count($headerColumns)} columns"
                );
            }
            
            // Number of data rows should match source data (up to 10)
            $expectedRows = min(10, $topProducts->count());
            $this->assertEquals(
                $expectedRows,
                count($topProductsSection),
                "Top Products section should have {$expectedRows} data rows"
            );
        }

        // Property 3: Verify Sales by Category section data integrity
        $categorySection = $this->extractSection($lines, 'Sales by Category');
        if (!empty($categorySection)) {
            $headerRow = array_shift($categorySection);
            $headerColumns = str_getcsv($headerRow);
            
            foreach ($categorySection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Sales by Category should have {count($headerColumns)} columns"
                );
            }
            
            $this->assertEquals(
                $salesByCategory->count(),
                count($categorySection),
                "Sales by Category section should have {$salesByCategory->count()} data rows"
            );
        }

        // Property 4: Verify Sales by Brand section data integrity
        $brandSection = $this->extractSection($lines, 'Sales by Brand');
        if (!empty($brandSection)) {
            $headerRow = array_shift($brandSection);
            $headerColumns = str_getcsv($headerRow);
            
            foreach ($brandSection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Sales by Brand should have {count($headerColumns)} columns"
                );
            }
            
            $this->assertEquals(
                $salesByBrand->count(),
                count($brandSection),
                "Sales by Brand section should have {$salesByBrand->count()} data rows"
            );
        }

        // Property 5: Verify Payment Method Distribution section data integrity
        $paymentSection = $this->extractSection($lines, 'Payment Method Distribution');
        if (!empty($paymentSection)) {
            $headerRow = array_shift($paymentSection);
            $headerColumns = str_getcsv($headerRow);
            
            foreach ($paymentSection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Payment Method Distribution should have {count($headerColumns)} columns"
                );
            }
            
            $this->assertEquals(
                $paymentDistribution->count(),
                count($paymentSection),
                "Payment Method Distribution section should have {$paymentDistribution->count()} data rows"
            );
        }

        // Property 6: Verify Revenue by Location section data integrity
        $locationSection = $this->extractSection($lines, 'Revenue by Location');
        if (!empty($locationSection)) {
            $headerRow = array_shift($locationSection);
            $headerColumns = str_getcsv($headerRow);
            
            foreach ($locationSection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Revenue by Location should have {count($headerColumns)} columns"
                );
            }
            
            $this->assertEquals(
                $revenueByLocation->count(),
                count($locationSection),
                "Revenue by Location section should have {$revenueByLocation->count()} data rows"
            );
        }

        // Property 7: Verify Channel Comparison has exactly 2 rows (walk-in and online)
        $channelSection = $this->extractSection($lines, 'Channel Comparison');
        if (!empty($channelSection)) {
            $headerRow = array_shift($channelSection);
            $headerColumns = str_getcsv($headerRow);
            
            foreach ($channelSection as $row) {
                $columns = str_getcsv($row);
                $this->assertEquals(
                    count($headerColumns),
                    count($columns),
                    "Each row in Channel Comparison should have {count($headerColumns)} columns"
                );
            }
            
            $this->assertEquals(
                2,
                count($channelSection),
                "Channel Comparison section should have exactly 2 data rows (walk-in and online)"
            );
        }
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
