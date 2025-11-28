<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AnalyticsPerformanceBenchmark extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;
    protected array $benchmarkResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Benchmark analytics queries with 1k orders
     */
    public function test_benchmark_with_1k_orders(): void
    {
        $this->seedDatabase(1000);
        $this->runBenchmarks('1k orders');
        $this->outputResults();
    }

    /**
     * Benchmark analytics queries with 10k orders
     * 
     * @group slow
     */
    public function test_benchmark_with_10k_orders(): void
    {
        $this->seedDatabase(10000);
        $this->runBenchmarks('10k orders');
        $this->outputResults();
    }

    /**
     * Benchmark analytics queries with 100k orders
     * 
     * @group slow
     * @group very-slow
     */
    public function test_benchmark_with_100k_orders(): void
    {
        $this->seedDatabase(100000);
        $this->runBenchmarks('100k orders');
        $this->outputResults();
    }

    /**
     * Seed the database with test data
     */
    protected function seedDatabase(int $orderCount): void
    {
        echo "\n\nSeeding database with {$orderCount} orders...\n";
        $startTime = microtime(true);

        // Create base data
        $categories = Category::factory()->count(10)->create();
        $brands = Brand::factory()->count(20)->create();
        $products = Product::factory()->count(100)->create([
            'category_id' => fn() => $categories->random()->id,
            'brand_id' => fn() => $brands->random()->id,
        ]);
        
        // Create inventory for products
        foreach ($products as $product) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'location' => 'main_warehouse',
                'quantity_available' => rand(0, 100),
                'reorder_level' => 20,
            ]);
        }

        $customers = User::factory()->count(min(1000, $orderCount / 10))->create([
            'role' => 'customer',
        ]);

        // Create orders in batches for better performance
        $batchSize = 1000;
        $batches = ceil($orderCount / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $orderCount - ($batch * $batchSize));
            
            DB::transaction(function () use ($currentBatchSize, $customers, $products, $batch, $batchSize) {
                $orders = [];
                $orderItems = [];
                $payments = [];
                
                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $orderType = rand(0, 1) ? 'walk_in' : 'online';
                    $orderNumber = ($orderType === 'walk_in' ? 'WI-' : 'ON-') . 
                                   now()->format('Ymd') . '-' . 
                                   str_pad(($batch * $batchSize + $i + 1), 6, '0', STR_PAD_LEFT);
                    
                    $subtotal = rand(100, 10000);
                    $taxAmount = $subtotal * 0.12;
                    $shippingCost = rand(0, 100);
                    $discountAmount = 0;
                    $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;
                    
                    $orderId = DB::table('orders')->insertGetId([
                        'order_number' => $orderNumber,
                        'user_id' => $customers->random()->id,
                        'order_type' => $orderType,
                        'order_status' => $this->randomOrderStatus(),
                        'payment_status' => rand(0, 1) ? 'paid' : 'pending',
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'shipping_cost' => $shippingCost,
                        'discount_amount' => $discountAmount,
                        'total_amount' => $totalAmount,
                        'created_at' => $this->randomDate(),
                        'updated_at' => now(),
                    ]);

                    // Create 1-5 order items per order
                    $itemCount = rand(1, 5);
                    for ($j = 0; $j < $itemCount; $j++) {
                        $product = $products->random();
                        $quantity = rand(1, 5);
                        $unitPrice = rand(50, 2000);
                        $totalPrice = $quantity * $unitPrice;
                        
                        DB::table('order_items')->insert([
                            'order_id' => $orderId,
                            'product_id' => $product->id,
                            'variant_id' => null,
                            'product_name' => $product->name,
                            'variant_name' => null,
                            'sku' => $product->sku,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount_amount' => 0,
                            'tax_amount' => 0,
                            'total_price' => $totalPrice,
                            'created_at' => now(),
                        ]);
                    }

                    // Create payment
                    DB::table('payments')->insert([
                        'order_id' => $orderId,
                        'payment_method' => $this->randomPaymentMethod(),
                        'payment_status' => 'completed',
                        'amount' => $totalAmount,
                        'transaction_id' => 'TXN-' . uniqid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            if (($batch + 1) % 10 === 0) {
                echo "  Seeded " . (($batch + 1) * $batchSize) . " orders...\n";
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        echo "Database seeded in {$duration} seconds\n\n";
    }

    /**
     * Run all benchmark tests
     */
    protected function runBenchmarks(string $label): void
    {
        echo "Running benchmarks for {$label}...\n\n";

        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();

        // Benchmark each analytics method
        $this->benchmarkMethod('calculateRevenue', function () use ($startDate, $endDate) {
            return $this->analyticsService->calculateRevenue($startDate, $endDate);
        });

        $this->benchmarkMethod('getOrderMetrics', function () use ($startDate, $endDate) {
            return $this->analyticsService->getOrderMetrics($startDate, $endDate);
        });

        $this->benchmarkMethod('getTopSellingProducts', function () {
            return $this->analyticsService->getTopSellingProducts(10, 'month');
        });

        $this->benchmarkMethod('getSalesByCategory', function () {
            return $this->analyticsService->getSalesByCategory('month');
        });

        $this->benchmarkMethod('getSalesByBrand', function () {
            return $this->analyticsService->getSalesByBrand('month');
        });

        $this->benchmarkMethod('getPaymentMethodDistribution', function () {
            return $this->analyticsService->getPaymentMethodDistribution('month');
        });

        $this->benchmarkMethod('getChannelComparison', function () {
            return $this->analyticsService->getChannelComparison('month');
        });

        $this->benchmarkMethod('getDailySalesTrend', function () {
            return $this->analyticsService->getDailySalesTrend('month');
        });

        $this->benchmarkMethod('getInventoryAlerts', function () {
            return $this->analyticsService->getInventoryAlerts();
        });

        $this->benchmarkMethod('getCustomerMetrics', function () {
            return $this->analyticsService->getCustomerMetrics('month');
        });

        $this->benchmarkMethod('getProfitMetrics', function () {
            return $this->analyticsService->getProfitMetrics('month');
        });

        // Test with cache (second run)
        echo "\nTesting with cache (second run)...\n";
        $this->benchmarkMethod('calculateRevenue (cached)', function () use ($startDate, $endDate) {
            return $this->analyticsService->calculateRevenue($startDate, $endDate);
        });
    }

    /**
     * Benchmark a single method
     */
    protected function benchmarkMethod(string $name, callable $method): void
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Execute the method
        $result = $method();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2); // Convert to MB
        $queryCount = count($queries);

        $this->benchmarkResults[$name] = [
            'duration_ms' => $duration,
            'memory_mb' => $memoryUsed,
            'query_count' => $queryCount,
            'queries' => $queries,
        ];

        echo sprintf(
            "  %-35s %8.2f ms  |  %6.2f MB  |  %3d queries\n",
            $name,
            $duration,
            $memoryUsed,
            $queryCount
        );

        // Flag slow queries (> 1 second)
        if ($duration > 1000) {
            echo "    ⚠️  SLOW QUERY DETECTED!\n";
        }
    }

    /**
     * Output detailed benchmark results
     */
    protected function outputResults(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "BENCHMARK SUMMARY\n";
        echo str_repeat('=', 80) . "\n\n";

        $totalDuration = array_sum(array_column($this->benchmarkResults, 'duration_ms'));
        $totalMemory = array_sum(array_column($this->benchmarkResults, 'memory_mb'));
        $totalQueries = array_sum(array_column($this->benchmarkResults, 'query_count'));

        echo sprintf("Total Duration: %.2f ms (%.2f seconds)\n", $totalDuration, $totalDuration / 1000);
        echo sprintf("Total Memory: %.2f MB\n", $totalMemory);
        echo sprintf("Total Queries: %d\n\n", $totalQueries);

        // Identify slowest queries
        echo "SLOWEST METHODS:\n";
        $sorted = $this->benchmarkResults;
        uasort($sorted, fn($a, $b) => $b['duration_ms'] <=> $a['duration_ms']);
        
        $count = 0;
        foreach ($sorted as $name => $result) {
            if ($count++ >= 5) break;
            echo sprintf("  %d. %-35s %8.2f ms\n", $count, $name, $result['duration_ms']);
        }

        echo "\n";

        // Show detailed query information for slow methods
        echo "SLOW QUERY DETAILS (> 100ms):\n";
        foreach ($this->benchmarkResults as $name => $result) {
            if ($result['duration_ms'] > 100) {
                echo "\n{$name}:\n";
                foreach ($result['queries'] as $query) {
                    $time = round($query['time'], 2);
                    if ($time > 10) { // Show queries taking > 10ms
                        echo "  [{$time}ms] {$query['query']}\n";
                    }
                }
            }
        }

        echo "\n" . str_repeat('=', 80) . "\n\n";

        // Assert performance requirements
        $this->assertLessThan(3000, $totalDuration, 
            "Total analytics load time should be under 3 seconds (Requirement 15.1)");
    }

    /**
     * Generate random order status
     */
    protected function randomOrderStatus(): string
    {
        $statuses = ['completed', 'pending', 'processing', 'cancelled'];
        $weights = [70, 15, 10, 5]; // 70% completed, 15% pending, etc.
        
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $statuses[$index];
            }
        }
        
        return 'completed';
    }

    /**
     * Generate random payment method
     */
    protected function randomPaymentMethod(): string
    {
        $methods = ['cash', 'credit_card', 'debit_card', 'gcash', 'paymaya'];
        return $methods[array_rand($methods)];
    }

    /**
     * Generate random date within the last year
     */
    protected function randomDate(): Carbon
    {
        $daysAgo = rand(0, 365);
        return Carbon::now()->subDays($daysAgo);
    }
}
