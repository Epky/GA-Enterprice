<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 6: Average order value calculation
 * Validates: Requirements 3.1, 3.2, 3.3
 */
class AnalyticsAOVCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 6: Average order value calculation
     * For any set of completed orders, if the count is greater than 0, 
     * AOV should equal total_revenue / order_count, otherwise AOV should be 0
     * 
     * @test
     */
    public function property_aov_equals_total_revenue_divided_by_order_count()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            $completedOrders = [];
            $totalRevenue = 0;
            
            // Create completed orders with random amounts
            $completedCount = rand(5, 20);
            for ($i = 0; $i < $completedCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                $amount = rand(100, 10000) / 10; // Random amount between 10.0 and 1000.0
                
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'total_amount' => $amount,
                    'created_at' => $randomDate,
                ]);
                
                $completedOrders[] = $order;
                $totalRevenue += $amount;
            }
            
            // Create pending orders (should NOT be included in AOV)
            for ($i = 0; $i < rand(2, 5); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'pending',
                    'total_amount' => rand(100, 10000) / 10,
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create cancelled orders (should NOT be included in AOV)
            for ($i = 0; $i < rand(1, 3); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'cancelled',
                    'total_amount' => rand(100, 10000) / 10,
                    'created_at' => $randomDate,
                ]);
            }
            
            // Calculate expected AOV
            $expectedAOV = $completedCount > 0 ? $totalRevenue / $completedCount : 0.0;
            
            // Act: Get order metrics from service
            $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
            
            // Assert: AOV should match expected value (with small tolerance for floating point precision)
            $this->assertEqualsWithDelta(
                $expectedAOV,
                $result['avg_order_value'],
                0.01,
                "AOV should equal total revenue divided by completed order count (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            Order::query()->delete();
        }
    }

    /**
     * Property: AOV returns zero when no completed orders exist
     * 
     * @test
     */
    public function property_aov_returns_zero_when_no_completed_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create only pending orders
        Order::factory()->count(5)->create([
            'order_status' => 'pending',
            'total_amount' => 100.00,
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create only cancelled orders
        Order::factory()->count(3)->create([
            'order_status' => 'cancelled',
            'total_amount' => 200.00,
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['avg_order_value'], "AOV should be zero when no completed orders exist");
    }

    /**
     * Property: AOV returns zero when no orders exist at all
     * 
     * @test
     */
    public function property_aov_returns_zero_when_no_orders_exist()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // No orders created
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['avg_order_value'], "AOV should be zero when no orders exist");
    }

    /**
     * Property: AOV calculation only includes completed orders
     * 
     * @test
     */
    public function property_aov_only_includes_completed_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create completed orders with known amounts
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 100.00,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 200.00,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 300.00,
            'created_at' => Carbon::now()->subDays(20),
        ]);
        
        // Create pending orders with large amounts (should NOT affect AOV)
        Order::factory()->create([
            'order_status' => 'pending',
            'total_amount' => 10000.00,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        
        // Create processing orders (should NOT affect AOV)
        Order::factory()->create([
            'order_status' => 'processing',
            'total_amount' => 5000.00,
            'created_at' => Carbon::now()->subDays(8),
        ]);
        
        // Expected AOV: (100 + 200 + 300) / 3 = 200.00
        $expectedAOV = 200.00;
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEqualsWithDelta(
            $expectedAOV,
            $result['avg_order_value'],
            0.01,
            "AOV should only include completed orders"
        );
    }

    /**
     * Property: AOV with single completed order equals that order's amount
     * 
     * @test
     */
    public function property_aov_with_single_order_equals_order_amount()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $orderAmount = 456.78;
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => $orderAmount,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEqualsWithDelta(
            $orderAmount,
            $result['avg_order_value'],
            0.01,
            "AOV with single order should equal that order's amount"
        );
    }

    /**
     * Property: AOV calculation is consistent across multiple calls
     * 
     * @test
     */
    public function property_aov_calculation_is_consistent()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create completed orders
        Order::factory()->count(10)->create([
            'order_status' => 'completed',
            'total_amount' => 150.00,
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act: Call the method multiple times
        $result1 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        $result2 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        $result3 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert: All results should be identical
        $this->assertEquals($result1['avg_order_value'], $result2['avg_order_value'], "AOV should be consistent");
        $this->assertEquals($result2['avg_order_value'], $result3['avg_order_value'], "AOV should be consistent");
    }

    /**
     * Property: AOV handles orders with zero amount
     * 
     * @test
     */
    public function property_aov_handles_orders_with_zero_amount()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create completed orders with zero amount (e.g., promotional orders)
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 0.00,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 0.00,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        
        // Expected AOV: (0 + 0) / 2 = 0.00
        $expectedAOV = 0.00;
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals($expectedAOV, $result['avg_order_value'], "AOV should handle zero amount orders");
    }

    /**
     * Property: AOV handles mix of zero and non-zero amounts
     * 
     * @test
     */
    public function property_aov_handles_mix_of_zero_and_nonzero_amounts()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create completed orders with mix of amounts
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 0.00,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 100.00,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 200.00,
            'created_at' => Carbon::now()->subDays(20),
        ]);
        
        // Expected AOV: (0 + 100 + 200) / 3 = 100.00
        $expectedAOV = 100.00;
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEqualsWithDelta(
            $expectedAOV,
            $result['avg_order_value'],
            0.01,
            "AOV should correctly handle mix of zero and non-zero amounts"
        );
    }

    /**
     * Property: AOV respects date boundaries
     * 
     * @test
     */
    public function property_aov_respects_date_boundaries()
    {
        // Arrange
        $startDate = Carbon::create(2024, 1, 15, 0, 0, 0);
        $endDate = Carbon::create(2024, 1, 20, 23, 59, 59);
        
        // Orders within range
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 100.00,
            'created_at' => $startDate,
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 200.00,
            'created_at' => $endDate,
        ]);
        
        // Orders outside range (should NOT be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 1000.00,
            'created_at' => $startDate->copy()->subSecond(),
        ]);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 2000.00,
            'created_at' => $endDate->copy()->addSecond(),
        ]);
        
        // Expected AOV: (100 + 200) / 2 = 150.00
        $expectedAOV = 150.00;
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEqualsWithDelta(
            $expectedAOV,
            $result['avg_order_value'],
            0.01,
            "AOV should only include orders within date boundaries"
        );
    }
}
