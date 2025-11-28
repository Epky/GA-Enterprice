<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 1: Revenue calculation includes only valid orders
 * Validates: Requirements 1.1, 1.2
 */
class AnalyticsRevenueCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 1: Revenue calculation includes only valid orders
     * For any date range, the calculated revenue should equal the sum of total_amount 
     * from orders where order_status is 'completed' AND payment_status is 'paid' 
     * AND created_at is within the date range
     * 
     * @test
     */
    public function property_revenue_includes_only_completed_and_paid_orders()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            // Create orders with various statuses and dates
            $ordersInRange = [];
            $ordersOutOfRange = [];
            
            // Create completed + paid orders within range (should be included)
            for ($i = 0; $i < rand(5, 15); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $randomDate,
                ]);
                $ordersInRange[] = $order;
            }
            
            // Create completed but not paid orders within range (should NOT be included)
            for ($i = 0; $i < rand(2, 5); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'pending',
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create paid but not completed orders within range (should NOT be included)
            for ($i = 0; $i < rand(2, 5); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'pending',
                    'payment_status' => 'paid',
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create cancelled orders within range (should NOT be included)
            for ($i = 0; $i < rand(1, 3); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'cancelled',
                    'payment_status' => 'refunded',
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create completed + paid orders outside range (should NOT be included)
            for ($i = 0; $i < rand(2, 5); $i++) {
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $startDate->copy()->subDays(rand(1, 30)),
                ]);
            }
            
            // Calculate expected revenue (manual sum of valid orders)
            $expectedRevenue = collect($ordersInRange)->sum('total_amount');
            
            // Act: Get revenue from service
            $result = $this->analyticsService->calculateRevenue($startDate, $endDate);
            
            // Assert: Revenue should match expected value (with small tolerance for floating point precision)
            $this->assertEqualsWithDelta(
                $expectedRevenue,
                $result['total'],
                0.1,
                "Revenue should only include completed and paid orders within date range (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            Order::query()->delete();
        }
    }

    /**
     * Property: Revenue calculation with no valid orders returns zero
     * 
     * @test
     */
    public function property_revenue_returns_zero_when_no_valid_orders_exist()
    {
        // Arrange: Create only invalid orders
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create pending orders
        Order::factory()->count(5)->create([
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create cancelled orders
        Order::factory()->count(3)->create([
            'order_status' => 'cancelled',
            'payment_status' => 'refunded',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->calculateRevenue($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['total'], "Revenue should be zero when no valid orders exist");
    }

    /**
     * Property: Revenue calculation handles empty date range
     * 
     * @test
     */
    public function property_revenue_handles_empty_date_range()
    {
        // Arrange: Create orders but query a date range with no orders
        Order::factory()->count(5)->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subYears(2),
        ]);
        
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Act
        $result = $this->analyticsService->calculateRevenue($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0.0, $result['total'], "Revenue should be zero for date range with no orders");
    }

    /**
     * Property: Revenue calculation is consistent across multiple calls
     * 
     * @test
     */
    public function property_revenue_calculation_is_consistent()
    {
        // Arrange: Create fixed set of orders
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        Order::factory()->count(10)->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act: Call the method multiple times
        $result1 = $this->analyticsService->calculateRevenue($startDate, $endDate);
        $result2 = $this->analyticsService->calculateRevenue($startDate, $endDate);
        $result3 = $this->analyticsService->calculateRevenue($startDate, $endDate);
        
        // Assert: All results should be identical
        $this->assertEquals($result1['total'], $result2['total'], "Revenue calculation should be consistent");
        $this->assertEquals($result2['total'], $result3['total'], "Revenue calculation should be consistent");
    }

    /**
     * Property: Revenue calculation respects exact date boundaries
     * 
     * @test
     */
    public function property_revenue_respects_exact_date_boundaries()
    {
        // Arrange: Create orders at boundary dates
        $startDate = Carbon::create(2024, 1, 15, 0, 0, 0);
        $endDate = Carbon::create(2024, 1, 20, 23, 59, 59);
        
        // Order exactly at start boundary (should be included)
        $orderAtStart = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 100.00,
            'created_at' => $startDate,
        ]);
        
        // Order exactly at end boundary (should be included)
        $orderAtEnd = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 200.00,
            'created_at' => $endDate,
        ]);
        
        // Order just before start (should NOT be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 50.00,
            'created_at' => $startDate->copy()->subSecond(),
        ]);
        
        // Order just after end (should NOT be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 75.00,
            'created_at' => $endDate->copy()->addSecond(),
        ]);
        
        // Act
        $result = $this->analyticsService->calculateRevenue($startDate, $endDate);
        
        // Assert: Should only include orders at boundaries
        $expectedRevenue = 300.00; // 100 + 200
        $this->assertEquals(
            $expectedRevenue,
            $result['total'],
            "Revenue should include orders at exact boundaries but exclude orders outside"
        );
    }
}
