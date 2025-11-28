<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsPaymentAndChannelTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /** @test */
    public function test_payment_method_distribution_returns_collection()
    {
        $result = $this->analyticsService->getPaymentMethodDistribution('month');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function test_payment_method_distribution_with_orders()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create orders with payments
        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 1000,
            'created_at' => Carbon::now(),
        ]);
        
        Payment::create([
            'order_id' => $order1->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_status' => 'completed',
        ]);
        
        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 2000,
            'created_at' => Carbon::now(),
        ]);
        
        Payment::create([
            'order_id' => $order2->id,
            'payment_method' => 'credit_card',
            'amount' => 2000,
            'payment_status' => 'completed',
        ]);
        
        $result = $this->analyticsService->getPaymentMethodDistribution('month');
        
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('payment_method', 'cash'));
        $this->assertTrue($result->contains('payment_method', 'credit_card'));
    }

    /** @test */
    public function test_channel_comparison_returns_array()
    {
        $result = $this->analyticsService->getChannelComparison('month');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('walk_in', $result);
        $this->assertArrayHasKey('online', $result);
    }

    /** @test */
    public function test_channel_comparison_with_orders()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create walk-in order
        Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'walk_in',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 1000,
            'created_at' => Carbon::now(),
        ]);
        
        // Create online order
        Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'online',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 2000,
            'created_at' => Carbon::now(),
        ]);
        
        $result = $this->analyticsService->getChannelComparison('month');
        
        $this->assertEquals(1000, $result['walk_in']['revenue']);
        $this->assertEquals(1, $result['walk_in']['order_count']);
        $this->assertEquals(2000, $result['online']['revenue']);
        $this->assertEquals(1, $result['online']['order_count']);
        
        // Check percentages
        $this->assertEquals(33.33, $result['walk_in']['revenue_percentage']);
        $this->assertEquals(66.67, $result['online']['revenue_percentage']);
    }
}
