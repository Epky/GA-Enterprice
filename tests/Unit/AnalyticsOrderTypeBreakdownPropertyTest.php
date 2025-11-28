<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 5: Order type breakdown sums to total
 * Validates: Requirements 2.4
 */
class AnalyticsOrderTypeBreakdownPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 5: Order type breakdown sums to total
     * For any date range, the sum of walk-in order count and online order count 
     * should equal the total order count
     * 
     * @test
     */
    public function property_order_type_breakdown_sums_to_total()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            // Create walk-in orders within range
            $walkInCount = rand(5, 15);
            for ($i = 0; $i < $walkInCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_type' => 'walk_in',
                    'order_status' => ['completed', 'pending', 'processing'][rand(0, 2)],
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create online orders within range
            $onlineCount = rand(5, 15);
            for ($i = 0; $i < $onlineCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_type' => 'online',
                    'order_status' => ['completed', 'pending', 'processing'][rand(0, 2)],
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create cancelled orders (should not be counted in any category)
            for ($i = 0; $i < rand(2, 5); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_type' => ['walk_in', 'online'][rand(0, 1)],
                    'order_status' => 'cancelled',
                    'created_at' => $randomDate,
                ]);
            }
            
            // Act: Get order metrics from service
            $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
            
            // Assert: Walk-in + Online should equal Total
            $sumOfTypes = $result['walk_in_orders'] + $result['online_orders'];
            $this->assertEquals(
                $result['total_orders'],
                $sumOfTypes,
                "Sum of walk-in ({$result['walk_in_orders']}) and online ({$result['online_orders']}) orders should equal total orders ({$result['total_orders']}) (iteration {$iteration})"
            );
            
            // Also verify the individual counts match what we created
            $this->assertEquals(
                $walkInCount,
                $result['walk_in_orders'],
                "Walk-in order count should match created orders (iteration {$iteration})"
            );
            
            $this->assertEquals(
                $onlineCount,
                $result['online_orders'],
                "Online order count should match created orders (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            Order::query()->delete();
        }
    }

    /**
     * Property: Order type breakdown with only walk-in orders
     * 
     * @test
     */
    public function property_order_type_breakdown_with_only_walk_in_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create only walk-in orders
        Order::factory()->count(10)->create([
            'order_type' => 'walk_in',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(10, $result['total_orders'], "Total should be 10");
        $this->assertEquals(10, $result['walk_in_orders'], "Walk-in should be 10");
        $this->assertEquals(0, $result['online_orders'], "Online should be 0");
        $this->assertEquals(
            $result['total_orders'],
            $result['walk_in_orders'] + $result['online_orders'],
            "Sum should equal total"
        );
    }

    /**
     * Property: Order type breakdown with only online orders
     * 
     * @test
     */
    public function property_order_type_breakdown_with_only_online_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create only online orders
        Order::factory()->count(8)->create([
            'order_type' => 'online',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(8, $result['total_orders'], "Total should be 8");
        $this->assertEquals(0, $result['walk_in_orders'], "Walk-in should be 0");
        $this->assertEquals(8, $result['online_orders'], "Online should be 8");
        $this->assertEquals(
            $result['total_orders'],
            $result['walk_in_orders'] + $result['online_orders'],
            "Sum should equal total"
        );
    }

    /**
     * Property: Order type breakdown with no orders
     * 
     * @test
     */
    public function property_order_type_breakdown_with_no_orders()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // No orders created
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0, $result['total_orders'], "Total should be 0");
        $this->assertEquals(0, $result['walk_in_orders'], "Walk-in should be 0");
        $this->assertEquals(0, $result['online_orders'], "Online should be 0");
        $this->assertEquals(
            $result['total_orders'],
            $result['walk_in_orders'] + $result['online_orders'],
            "Sum should equal total (0 = 0 + 0)"
        );
    }

    /**
     * Property: Order type breakdown excludes cancelled orders from both types
     * 
     * @test
     */
    public function property_order_type_breakdown_excludes_cancelled_from_both_types()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create valid walk-in orders
        Order::factory()->count(5)->create([
            'order_type' => 'walk_in',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create valid online orders
        Order::factory()->count(3)->create([
            'order_type' => 'online',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create cancelled walk-in orders (should not be counted)
        Order::factory()->count(4)->create([
            'order_type' => 'walk_in',
            'order_status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create cancelled online orders (should not be counted)
        Order::factory()->count(2)->create([
            'order_type' => 'online',
            'order_status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(8, $result['total_orders'], "Total should be 8 (5 walk-in + 3 online)");
        $this->assertEquals(5, $result['walk_in_orders'], "Walk-in should be 5 (excluding cancelled)");
        $this->assertEquals(3, $result['online_orders'], "Online should be 3 (excluding cancelled)");
        $this->assertEquals(
            $result['total_orders'],
            $result['walk_in_orders'] + $result['online_orders'],
            "Sum should equal total"
        );
    }

    /**
     * Property: Order type breakdown is consistent across multiple calls
     * 
     * @test
     */
    public function property_order_type_breakdown_is_consistent()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        Order::factory()->count(7)->create([
            'order_type' => 'walk_in',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        Order::factory()->count(5)->create([
            'order_type' => 'online',
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act: Call the method multiple times
        $result1 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        $result2 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        $result3 = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert: All results should be identical
        $this->assertEquals($result1['walk_in_orders'], $result2['walk_in_orders'], "Walk-in counts should be consistent");
        $this->assertEquals($result2['walk_in_orders'], $result3['walk_in_orders'], "Walk-in counts should be consistent");
        $this->assertEquals($result1['online_orders'], $result2['online_orders'], "Online counts should be consistent");
        $this->assertEquals($result2['online_orders'], $result3['online_orders'], "Online counts should be consistent");
        
        // Verify sum property holds for all calls
        foreach ([$result1, $result2, $result3] as $result) {
            $this->assertEquals(
                $result['total_orders'],
                $result['walk_in_orders'] + $result['online_orders'],
                "Sum should equal total for each call"
            );
        }
    }
}
