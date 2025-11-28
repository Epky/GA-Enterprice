<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 16: Profit calculation accuracy
 * Validates: Requirements 14.1, 14.2, 14.4
 */
class AnalyticsProfitCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 16: Profit calculation accuracy
     * For any set of completed orders, gross profit should equal the sum of 
     * (sale_price - cost_price) * quantity for all order items where cost_price is not null
     * 
     * @test
     */
    public function property_profit_calculation_accuracy()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            // Create shared category and brand to avoid unique constraint violations
            $category = \App\Models\Category::factory()->create();
            $brand = \App\Models\Brand::factory()->create();
            
            // Create products with cost prices
            $productsWithCost = [];
            for ($i = 0; $i < rand(5, 10); $i++) {
                $costPrice = rand(10, 100) + (rand(0, 99) / 100);
                $salePrice = $costPrice * (1 + rand(20, 100) / 100); // 20-100% markup
                
                $productsWithCost[] = Product::factory()->create([
                    'cost_price' => $costPrice,
                    'base_price' => $salePrice,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                ]);
            }
            
            // Create products without cost prices (should be excluded)
            $productsWithoutCost = [];
            for ($i = 0; $i < rand(2, 5); $i++) {
                $productsWithoutCost[] = Product::factory()->create([
                    'cost_price' => null,
                    'base_price' => rand(50, 200),
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                ]);
            }
            
            // Create completed orders with order items
            $expectedProfit = 0.0;
            $expectedRevenue = 0.0;
            $expectedCost = 0.0;
            
            for ($i = 0; $i < rand(5, 15); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $randomDate,
                    'total_amount' => 0, // Will be calculated
                ]);
                
                $orderTotal = 0.0;
                
                // Add items with cost prices
                foreach (array_slice($productsWithCost, 0, rand(1, 3)) as $product) {
                    $quantity = rand(1, 5);
                    $unitPrice = $product->base_price;
                    
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                    
                    $itemRevenue = $quantity * $unitPrice;
                    $itemCost = $quantity * $product->cost_price;
                    $itemProfit = $itemRevenue - $itemCost;
                    
                    $expectedRevenue += $itemRevenue;
                    $expectedCost += $itemCost;
                    $expectedProfit += $itemProfit;
                    $orderTotal += $itemRevenue;
                }
                
                // Add items without cost prices (should be excluded from profit calculation)
                if (rand(0, 1) && count($productsWithoutCost) > 0) {
                    $product = $productsWithoutCost[array_rand($productsWithoutCost)];
                    $quantity = rand(1, 3);
                    $unitPrice = $product->base_price;
                    
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                    
                    $orderTotal += $quantity * $unitPrice;
                }
                
                // Update order total
                $order->update(['total_amount' => $orderTotal]);
            }
            
            // Create some non-completed orders (should be excluded)
            for ($i = 0; $i < rand(2, 5); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                $order = Order::factory()->create([
                    'order_status' => 'pending',
                    'payment_status' => 'pending',
                    'created_at' => $randomDate,
                ]);
                
                $product = $productsWithCost[array_rand($productsWithCost)];
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 3),
                    'unit_price' => $product->base_price,
                ]);
            }
            
            // Act: Get profit metrics from service
            $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
            
            // Assert: Profit should match expected value (with small tolerance for floating point precision)
            $this->assertEqualsWithDelta(
                $expectedProfit,
                $result['gross_profit'],
                0.1,
                "Gross profit should equal sum of (sale_price - cost_price) * quantity for items with cost_price (iteration {$iteration})"
            );
            
            $this->assertEqualsWithDelta(
                $expectedRevenue,
                $result['total_revenue'],
                0.1,
                "Total revenue should match expected value (iteration {$iteration})"
            );
            
            $this->assertEqualsWithDelta(
                $expectedCost,
                $result['total_cost'],
                0.1,
                "Total cost should match expected value (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            OrderItem::query()->delete();
            Order::query()->delete();
            Product::query()->delete();
            \App\Models\Category::query()->delete();
            \App\Models\Brand::query()->delete();
        }
    }

    /**
     * Property: Profit calculation excludes products with null cost_price
     * 
     * @test
     */
    public function property_profit_excludes_null_cost_price_products()
    {
        // Arrange: Create products without cost prices
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $productsWithoutCost = Product::factory()->count(5)->create([
            'cost_price' => null,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create completed orders with these products
        foreach ($productsWithoutCost as $product) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subDays(rand(1, 29)),
                'total_amount' => 100.00,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100.00,
            ]);
        }
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert: All values should be zero since all products have null cost_price
        $this->assertEquals(0.0, $result['gross_profit'], "Profit should be zero when all products have null cost_price");
        $this->assertEquals(0.0, $result['total_revenue'], "Revenue should be zero when all products have null cost_price");
        $this->assertEquals(0.0, $result['total_cost'], "Cost should be zero when all products have null cost_price");
    }

    /**
     * Property: Profit calculation with no orders returns zero
     * 
     * @test
     */
    public function property_profit_returns_zero_when_no_orders_exist()
    {
        // Arrange: No orders created
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['gross_profit'], "Profit should be zero when no orders exist");
        $this->assertEquals(0.0, $result['total_revenue'], "Revenue should be zero when no orders exist");
        $this->assertEquals(0.0, $result['total_cost'], "Cost should be zero when no orders exist");
        $this->assertEquals(0.0, $result['profit_margin'], "Profit margin should be zero when no orders exist");
    }

    /**
     * Property: Profit calculation only includes completed orders
     * 
     * @test
     */
    public function property_profit_only_includes_completed_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $product = Product::factory()->create([
            'cost_price' => 50.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create completed order (should be included)
        $completedOrder = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Create pending order (should NOT be included)
        $pendingOrder = Order::factory()->create([
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => Carbon::now()->subDays(10),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Create cancelled order (should NOT be included)
        $cancelledOrder = Order::factory()->create([
            'order_status' => 'cancelled',
            'payment_status' => 'refunded',
            'created_at' => Carbon::now()->subDays(5),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert: Should only include the completed order
        $expectedProfit = 50.00; // (100 - 50) * 1
        $this->assertEquals($expectedProfit, $result['gross_profit'], "Profit should only include completed orders");
        $this->assertEquals(100.00, $result['total_revenue'], "Revenue should only include completed orders");
        $this->assertEquals(50.00, $result['total_cost'], "Cost should only include completed orders");
    }

    /**
     * Property: Profit calculation respects date range boundaries
     * 
     * @test
     */
    public function property_profit_respects_date_range_boundaries()
    {
        // Arrange
        $startDate = Carbon::create(2024, 1, 15, 0, 0, 0);
        $endDate = Carbon::create(2024, 1, 20, 23, 59, 59);
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $product = Product::factory()->create([
            'cost_price' => 50.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Order within range (should be included)
        $orderInRange = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::create(2024, 1, 17, 12, 0, 0),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $orderInRange->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Order before range (should NOT be included)
        $orderBefore = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::create(2024, 1, 14, 23, 59, 59),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $orderBefore->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Order after range (should NOT be included)
        $orderAfter = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::create(2024, 1, 21, 0, 0, 1),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $orderAfter->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert: Should only include order within range
        $expectedProfit = 50.00; // (100 - 50) * 1
        $this->assertEquals($expectedProfit, $result['gross_profit'], "Profit should only include orders within date range");
        $this->assertEquals(100.00, $result['total_revenue'], "Revenue should only include orders within date range");
        $this->assertEquals(50.00, $result['total_cost'], "Cost should only include orders within date range");
    }
}
