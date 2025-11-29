<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 8: Channel percentage distribution sums to 100
 * 
 * Property: For any channel comparison display, the sum of walk-in percentage 
 * and online percentage should equal 100%
 * 
 * Validates: Requirements 3.3
 */
class AnalyticsChannelPercentageDistributionPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * @test
     * Property: For any channel comparison display, the sum of walk-in percentage 
     * and online percentage should equal 100%
     */
    public function channel_percentages_sum_to_100_percent()
    {
        // Run property test 100 times with different random data
        for ($i = 0; $i < 100; $i++) {
            // Clear data between iterations
            \App\Models\Order::query()->delete();
            \App\Models\User::query()->delete();
            
            // Generate random number of orders for each channel
            // Ensure at least one order exists to avoid division by zero
            $walkInOrderCount = rand(1, 20);
            $onlineOrderCount = rand(1, 20);
            
            // Create a user
            $user = User::factory()->create();
            
            // Create random walk-in orders with random amounts
            for ($j = 0; $j < $walkInOrderCount; $j++) {
                Order::factory()->create([
                    'user_id' => $user->id,
                    'order_type' => 'walk_in',
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'total_amount' => rand(100, 10000),
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                ]);
            }
            
            // Create random online orders with random amounts
            for ($j = 0; $j < $onlineOrderCount; $j++) {
                Order::factory()->create([
                    'user_id' => $user->id,
                    'order_type' => 'online',
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'total_amount' => rand(100, 10000),
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                ]);
            }
            
            // Get channel comparison data
            $result = $this->analyticsService->getChannelComparison('month');
            
            // Property: Revenue percentages must sum to 100% (with small tolerance for rounding)
            $revenuePercentageSum = $result['walk_in']['revenue_percentage'] + $result['online']['revenue_percentage'];
            $this->assertEqualsWithDelta(100.0, $revenuePercentageSum, 0.1, 
                "Walk-in and online revenue percentages must sum to 100% (got {$revenuePercentageSum}%)");
            
            // Property: Order percentages must sum to 100% (with small tolerance for rounding)
            $orderPercentageSum = $result['walk_in']['order_percentage'] + $result['online']['order_percentage'];
            $this->assertEqualsWithDelta(100.0, $orderPercentageSum, 0.1, 
                "Walk-in and online order percentages must sum to 100% (got {$orderPercentageSum}%)");
            
            // Property: Each percentage should be between 0 and 100
            $this->assertGreaterThanOrEqual(0, $result['walk_in']['revenue_percentage'], 
                "Walk-in revenue percentage must be >= 0");
            $this->assertLessThanOrEqual(100, $result['walk_in']['revenue_percentage'], 
                "Walk-in revenue percentage must be <= 100");
            
            $this->assertGreaterThanOrEqual(0, $result['online']['revenue_percentage'], 
                "Online revenue percentage must be >= 0");
            $this->assertLessThanOrEqual(100, $result['online']['revenue_percentage'], 
                "Online revenue percentage must be <= 100");
            
            $this->assertGreaterThanOrEqual(0, $result['walk_in']['order_percentage'], 
                "Walk-in order percentage must be >= 0");
            $this->assertLessThanOrEqual(100, $result['walk_in']['order_percentage'], 
                "Walk-in order percentage must be <= 100");
            
            $this->assertGreaterThanOrEqual(0, $result['online']['order_percentage'], 
                "Online order percentage must be >= 0");
            $this->assertLessThanOrEqual(100, $result['online']['order_percentage'], 
                "Online order percentage must be <= 100");
        }
    }

    /**
     * @test
     * Edge case: When there are no orders, percentages should be 0
     */
    public function channel_percentages_are_zero_when_no_orders()
    {
        // Get channel comparison with no orders
        $result = $this->analyticsService->getChannelComparison('month');
        
        // All percentages should be 0
        $this->assertEquals(0.0, $result['walk_in']['revenue_percentage']);
        $this->assertEquals(0.0, $result['online']['revenue_percentage']);
        $this->assertEquals(0.0, $result['walk_in']['order_percentage']);
        $this->assertEquals(0.0, $result['online']['order_percentage']);
        
        // Sum should still be 0 (not 100 when there's no data)
        $revenueSum = $result['walk_in']['revenue_percentage'] + $result['online']['revenue_percentage'];
        $orderSum = $result['walk_in']['order_percentage'] + $result['online']['order_percentage'];
        
        $this->assertEquals(0.0, $revenueSum);
        $this->assertEquals(0.0, $orderSum);
    }
}
