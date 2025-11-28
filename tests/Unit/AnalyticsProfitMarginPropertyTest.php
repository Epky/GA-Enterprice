<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 17: Profit margin percentage calculation
 * Validates: Requirements 14.3
 */
class AnalyticsProfitMarginPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 17: Profit margin percentage calculation
     * For any profit calculation where revenue is greater than 0, 
     * profit_margin_percentage should equal (gross_profit / total_revenue) * 100
     * 
     * @test
     */
    public function property_profit_margin_percentage_calculation()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            // Create shared category and brand
            $category = \App\Models\Category::factory()->create();
            $brand = \App\Models\Brand::factory()->create();
            
            // Create products with cost prices
            $expectedRevenue = 0.0;
            $expectedCost = 0.0;
            
            for ($i = 0; $i < rand(5, 10); $i++) {
                $costPrice = rand(10, 100) + (rand(0, 99) / 100);
                $salePrice = $costPrice * (1 + rand(20, 100) / 100); // 20-100% markup
                
                $product = Product::factory()->create([
                    'cost_price' => $costPrice,
                    'base_price' => $salePrice,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                ]);
                
                // Create completed order with this product
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                $quantity = rand(1, 5);
                
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $randomDate,
                    'total_amount' => $salePrice * $quantity,
                ]);
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $salePrice,
                ]);
                
                $expectedRevenue += $salePrice * $quantity;
                $expectedCost += $costPrice * $quantity;
            }
            
            // Calculate expected profit margin
            $expectedGrossProfit = $expectedRevenue - $expectedCost;
            $expectedProfitMargin = $expectedRevenue > 0 
                ? round(($expectedGrossProfit / $expectedRevenue) * 100, 2)
                : 0.0;
            
            // Act: Get profit metrics from service
            $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
            
            // Assert: Profit margin should match expected value
            $this->assertEqualsWithDelta(
                $expectedProfitMargin,
                $result['profit_margin'],
                0.1,
                "Profit margin should equal (gross_profit / total_revenue) * 100 (iteration {$iteration})"
            );
            
            // Also verify the formula components
            $this->assertEqualsWithDelta(
                $expectedGrossProfit,
                $result['gross_profit'],
                0.1,
                "Gross profit should match expected value (iteration {$iteration})"
            );
            
            $this->assertEqualsWithDelta(
                $expectedRevenue,
                $result['total_revenue'],
                0.1,
                "Total revenue should match expected value (iteration {$iteration})"
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
     * Property: Profit margin is zero when revenue is zero
     * 
     * @test
     */
    public function property_profit_margin_is_zero_when_revenue_is_zero()
    {
        // Arrange: No orders
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['profit_margin'], "Profit margin should be zero when revenue is zero");
        $this->assertEquals(0.0, $result['total_revenue'], "Total revenue should be zero");
        $this->assertEquals(0.0, $result['gross_profit'], "Gross profit should be zero");
    }

    /**
     * Property: Profit margin is between 0 and 100 for positive profit
     * 
     * @test
     */
    public function property_profit_margin_is_between_0_and_100_for_positive_profit()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        // Create product with positive profit margin
        $product = Product::factory()->create([
            'cost_price' => 50.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        $this->assertGreaterThanOrEqual(0.0, $result['profit_margin'], "Profit margin should be >= 0");
        $this->assertLessThanOrEqual(100.0, $result['profit_margin'], "Profit margin should be <= 100");
        
        // For this specific case, profit margin should be 50%
        $this->assertEquals(50.0, $result['profit_margin'], "Profit margin should be 50% for this case");
    }

    /**
     * Property: Profit margin calculation is consistent
     * 
     * @test
     */
    public function property_profit_margin_calculation_is_consistent()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $product = Product::factory()->create([
            'cost_price' => 60.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act: Call the method multiple times
        $result1 = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        $result2 = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        $result3 = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert: All results should be identical
        $this->assertEquals($result1['profit_margin'], $result2['profit_margin'], "Profit margin calculation should be consistent");
        $this->assertEquals($result2['profit_margin'], $result3['profit_margin'], "Profit margin calculation should be consistent");
    }

    /**
     * Property: Profit margin handles high markup scenarios
     * 
     * @test
     */
    public function property_profit_margin_handles_high_markup()
    {
        // Arrange: Product with very high markup (200%)
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $product = Product::factory()->create([
            'cost_price' => 10.00,
            'base_price' => 30.00, // 200% markup
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 30.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 30.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        // Profit = 30 - 10 = 20
        // Margin = (20 / 30) * 100 = 66.67%
        $expectedMargin = round((20.0 / 30.0) * 100, 2);
        $this->assertEquals($expectedMargin, $result['profit_margin'], "Profit margin should handle high markup correctly");
    }

    /**
     * Property: Profit margin handles low markup scenarios
     * 
     * @test
     */
    public function property_profit_margin_handles_low_markup()
    {
        // Arrange: Product with very low markup (10%)
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        $product = Product::factory()->create([
            'cost_price' => 90.00,
            'base_price' => 100.00, // 11.11% markup
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        // Profit = 100 - 90 = 10
        // Margin = (10 / 100) * 100 = 10%
        $this->assertEquals(10.0, $result['profit_margin'], "Profit margin should handle low markup correctly");
    }

    /**
     * Property: Profit margin aggregates correctly across multiple products
     * 
     * @test
     */
    public function property_profit_margin_aggregates_across_products()
    {
        // Arrange: Multiple products with different margins
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $category = \App\Models\Category::factory()->create();
        $brand = \App\Models\Brand::factory()->create();
        
        // Product 1: 50% margin (cost: 50, price: 100)
        $product1 = Product::factory()->create([
            'cost_price' => 50.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Product 2: 25% margin (cost: 75, price: 100)
        $product2 = Product::factory()->create([
            'cost_price' => 75.00,
            'base_price' => 100.00,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create orders
        $order1 = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(15),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        $order2 = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(10),
            'total_amount' => 100.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $result = $this->analyticsService->getProfitMetrics('custom', $startDate, $endDate);
        
        // Assert
        // Total revenue: 200
        // Total cost: 125 (50 + 75)
        // Total profit: 75
        // Overall margin: (75 / 200) * 100 = 37.5%
        $this->assertEquals(200.0, $result['total_revenue'], "Total revenue should be 200");
        $this->assertEquals(125.0, $result['total_cost'], "Total cost should be 125");
        $this->assertEquals(75.0, $result['gross_profit'], "Gross profit should be 75");
        $this->assertEquals(37.5, $result['profit_margin'], "Profit margin should be 37.5%");
    }
}
