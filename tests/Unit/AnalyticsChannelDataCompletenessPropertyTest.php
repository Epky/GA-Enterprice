<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 7: Channel data completeness
 * 
 * Property: For any sales channel (walk-in or online), the channel data should 
 * include revenue amount and order count
 * 
 * Validates: Requirements 3.2
 */
class AnalyticsChannelDataCompletenessPropertyTest extends TestCase
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
     * Property: For any sales channel (walk-in or online), the channel data should 
     * include revenue amount and order count
     */
    public function channel_data_includes_revenue_and_order_count_for_all_channels()
    {
        // Run property test 100 times with different random data
        for ($i = 0; $i < 100; $i++) {
            // Clear data between iterations
            \App\Models\Order::query()->delete();
            \App\Models\User::query()->delete();
            
            // Generate random number of orders for each channel
            $walkInOrderCount = rand(0, 20);
            $onlineOrderCount = rand(0, 20);
            
            // Create a user
            $user = User::factory()->create();
            
            // Create random walk-in orders
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
            
            // Create random online orders
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
            
            // Property: Channel data must be an array with walk_in and online keys
            $this->assertIsArray($result, "Channel comparison should return an array");
            $this->assertArrayHasKey('walk_in', $result, "Channel data must include walk_in channel");
            $this->assertArrayHasKey('online', $result, "Channel data must include online channel");
            
            // Property: Walk-in channel must have revenue and order_count
            $this->assertArrayHasKey('revenue', $result['walk_in'], 
                "Walk-in channel data must include revenue");
            $this->assertArrayHasKey('order_count', $result['walk_in'], 
                "Walk-in channel data must include order_count");
            
            // Property: Online channel must have revenue and order_count
            $this->assertArrayHasKey('revenue', $result['online'], 
                "Online channel data must include revenue");
            $this->assertArrayHasKey('order_count', $result['online'], 
                "Online channel data must include order_count");
            
            // Property: Revenue should be numeric
            $this->assertIsNumeric($result['walk_in']['revenue'], 
                "Walk-in revenue must be numeric");
            $this->assertIsNumeric($result['online']['revenue'], 
                "Online revenue must be numeric");
            
            // Property: Order count should be integer
            $this->assertIsInt($result['walk_in']['order_count'], 
                "Walk-in order count must be integer");
            $this->assertIsInt($result['online']['order_count'], 
                "Online order count must be integer");
            
            // Property: Values should be non-negative
            $this->assertGreaterThanOrEqual(0, $result['walk_in']['revenue'], 
                "Walk-in revenue must be non-negative");
            $this->assertGreaterThanOrEqual(0, $result['online']['revenue'], 
                "Online revenue must be non-negative");
            $this->assertGreaterThanOrEqual(0, $result['walk_in']['order_count'], 
                "Walk-in order count must be non-negative");
            $this->assertGreaterThanOrEqual(0, $result['online']['order_count'], 
                "Online order count must be non-negative");
        }
    }
}
