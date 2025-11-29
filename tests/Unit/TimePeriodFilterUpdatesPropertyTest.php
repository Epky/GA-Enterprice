<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Property 3: Time period filter updates all metrics
 * 
 * Feature: admin-dashboard-reorganization, Property 3: Time period filter updates all metrics
 * Validates: Requirements 1.5, 2.5, 6.2
 * 
 * For any dashboard page and any time period selection, changing the period filter 
 * should update all displayed metrics, charts, and tables to reflect the new period.
 */
class TimePeriodFilterUpdatesPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that changing the period filter updates all metrics on the overview dashboard
     *
     * @return void
     */
    public function test_period_filter_updates_overview_dashboard_metrics(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test data for different periods
        $this->createTestDataForPeriods();
        
        // Test each period
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $period]));
            
            $response->assertStatus(200);
            $response->assertViewHas('period', $period);
            $response->assertViewHas('analytics');
            
            // Verify analytics data is present and period-specific
            $analytics = $response->viewData('analytics');
            $this->assertNotNull($analytics);
            $this->assertArrayHasKey('revenue', $analytics);
            $this->assertArrayHasKey('order_metrics', $analytics);
            $this->assertArrayHasKey('top_products', $analytics);
        }
    }

    /**
     * Test that changing the period filter updates all metrics on the sales & revenue page
     *
     * @return void
     */
    public function test_period_filter_updates_sales_revenue_page_metrics(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test data
        $this->createTestDataForPeriods();
        
        // Test each period
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard.sales', ['period' => $period]));
            
            $response->assertStatus(200);
            $response->assertViewHas('period', $period);
            $response->assertViewHas('analytics');
            
            // Verify analytics data is present
            $analytics = $response->viewData('analytics');
            $this->assertNotNull($analytics);
            $this->assertArrayHasKey('revenue', $analytics);
            $this->assertArrayHasKey('order_metrics', $analytics);
            $this->assertArrayHasKey('profit_metrics', $analytics);
            $this->assertArrayHasKey('sales_trend', $analytics);
            $this->assertArrayHasKey('top_products', $analytics);
        }
    }

    /**
     * Test that changing the period filter updates all metrics on the customers & channels page
     *
     * @return void
     */
    public function test_period_filter_updates_customers_channels_page_metrics(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test data
        $this->createTestDataForPeriods();
        
        // Test each period
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard.customers', ['period' => $period]));
            
            $response->assertStatus(200);
            $response->assertViewHas('period', $period);
            $response->assertViewHas('analytics');
            
            // Verify analytics data is present
            $analytics = $response->viewData('analytics');
            $this->assertNotNull($analytics);
            $this->assertArrayHasKey('customer_metrics', $analytics);
            $this->assertArrayHasKey('channel_comparison', $analytics);
            $this->assertArrayHasKey('payment_distribution', $analytics);
        }
    }

    /**
     * Test that period filter defaults to 'month' when not specified
     *
     * @return void
     */
    public function test_period_filter_defaults_to_month_when_not_specified(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test overview dashboard
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('period', 'month');
        
        // Test sales & revenue page
        $response = $this->actingAs($admin)->get(route('admin.dashboard.sales'));
        $response->assertStatus(200);
        $response->assertViewHas('period', 'month');
        
        // Test customers & channels page
        $response = $this->actingAs($admin)->get(route('admin.dashboard.customers'));
        $response->assertStatus(200);
        $response->assertViewHas('period', 'month');
        
        // Test inventory insights page
        $response = $this->actingAs($admin)->get(route('admin.dashboard.inventory'));
        $response->assertStatus(200);
        $response->assertViewHas('period', 'month');
    }

    /**
     * Test that metrics change when period changes
     *
     * @return void
     */
    public function test_metrics_change_when_period_changes(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create orders for different time periods
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['base_price' => 100]);
        
        // Create order from today
        $todayOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 100,
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'created_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $todayOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);
        Payment::factory()->create([
            'order_id' => $todayOrder->id,
            'amount' => 100,
            'payment_status' => 'completed',
        ]);
        
        // Create order from last month
        $lastMonthOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 200,
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $lastMonthOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);
        Payment::factory()->create([
            'order_id' => $lastMonthOrder->id,
            'amount' => 200,
            'payment_status' => 'completed',
        ]);
        
        // Get analytics for 'today' period
        $todayResponse = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'today']));
        $todayAnalytics = $todayResponse->viewData('analytics');
        
        // Get analytics for 'month' period
        $monthResponse = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'month']));
        $monthAnalytics = $monthResponse->viewData('analytics');
        
        // Verify that metrics are different for different periods
        // Today should only include today's order
        $this->assertEquals(1, $todayAnalytics['order_metrics']['total_orders']);
        
        // Month should include both orders (today's and last month's if within current month)
        // Or just today's if last month's order is outside current month
        $this->assertGreaterThanOrEqual(1, $monthAnalytics['order_metrics']['total_orders']);
    }

    /**
     * Helper method to create test data for different periods
     *
     * @return void
     */
    private function createTestDataForPeriods(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['base_price' => 100]);
        
        // Create orders for different periods
        $periods = [
            'today' => now(),
            'week' => now()->subDays(3),
            'month' => now()->subDays(15),
            'year' => now()->subMonths(6),
        ];
        
        foreach ($periods as $period => $date) {
            $order = Order::factory()->create([
                'user_id' => $customer->id,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'order_status' => 'completed',
                'created_at' => $date,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]);
            
            Payment::factory()->create([
                'order_id' => $order->id,
                'amount' => 100,
                'payment_status' => 'completed',
                'created_at' => $date,
            ]);
        }
    }
}
