<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 1: Summary cards display current and previous period data
 * 
 * Property: For any time period selection, all summary cards on the overview dashboard 
 * should display both current period values and percentage changes from the previous period
 * 
 * Validates: Requirements 1.2
 */
class SummaryCardDataCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that summary cards display both current and previous period data
     * 
     * @test
     */
    public function test_summary_cards_display_current_and_previous_period_data()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test with different time periods
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            // Act: Get the dashboard with the period
            $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $period]));
            
            // Assert: Response is successful
            $response->assertStatus(200);
            
            // Assert: Analytics data is present
            $response->assertViewHas('analytics');
            $analytics = $response->viewData('analytics');
            
            // Property: Revenue card should have current value and change percent
            $this->assertArrayHasKey('revenue', $analytics, "Revenue data missing for period: {$period}");
            $this->assertArrayHasKey('total', $analytics['revenue'], "Revenue total missing for period: {$period}");
            $this->assertArrayHasKey('change_percent', $analytics['revenue'], "Revenue change_percent missing for period: {$period}");
            
            // Property: Order metrics should have current values and change percents
            $this->assertArrayHasKey('order_metrics', $analytics, "Order metrics missing for period: {$period}");
            $this->assertArrayHasKey('total_orders', $analytics['order_metrics'], "Total orders missing for period: {$period}");
            $this->assertArrayHasKey('change_percent', $analytics['order_metrics'], "Order change_percent missing for period: {$period}");
            $this->assertArrayHasKey('avg_order_value', $analytics['order_metrics'], "AOV missing for period: {$period}");
            $this->assertArrayHasKey('aov_change_percent', $analytics['order_metrics'], "AOV change_percent missing for period: {$period}");
            
            // Property: Profit metrics should have current value and margin
            $this->assertArrayHasKey('profit_metrics', $analytics, "Profit metrics missing for period: {$period}");
            $this->assertArrayHasKey('gross_profit', $analytics['profit_metrics'], "Gross profit missing for period: {$period}");
            $this->assertArrayHasKey('profit_margin', $analytics['profit_metrics'], "Profit margin missing for period: {$period}");
            
            // Property: Channel comparison should have current values and percentages
            $this->assertArrayHasKey('channel_comparison', $analytics, "Channel comparison missing for period: {$period}");
            $this->assertArrayHasKey('walk_in', $analytics['channel_comparison'], "Walk-in channel missing for period: {$period}");
            $this->assertArrayHasKey('revenue', $analytics['channel_comparison']['walk_in'], "Walk-in revenue missing for period: {$period}");
            $this->assertArrayHasKey('percentage', $analytics['channel_comparison']['walk_in'], "Walk-in percentage missing for period: {$period}");
            $this->assertArrayHasKey('online', $analytics['channel_comparison'], "Online channel missing for period: {$period}");
            $this->assertArrayHasKey('revenue', $analytics['channel_comparison']['online'], "Online revenue missing for period: {$period}");
            $this->assertArrayHasKey('percentage', $analytics['channel_comparison']['online'], "Online percentage missing for period: {$period}");
            
            // Property: Customer metrics should have current value and growth rate
            $this->assertArrayHasKey('customer_metrics', $analytics, "Customer metrics missing for period: {$period}");
            $this->assertArrayHasKey('total_customers', $analytics['customer_metrics'], "Total customers missing for period: {$period}");
            $this->assertArrayHasKey('growth_rate', $analytics['customer_metrics'], "Customer growth rate missing for period: {$period}");
            
            // Property: Inventory alerts should have count
            $this->assertArrayHasKey('inventory_alerts', $analytics, "Inventory alerts missing for period: {$period}");
            $this->assertArrayHasKey('low_stock_count', $analytics['inventory_alerts'], "Low stock count missing for period: {$period}");
        }
    }
    
    /**
     * Test that summary cards display correct data with actual orders
     * 
     * @test
     */
    public function test_summary_cards_display_correct_data_with_orders()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test data
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100, 'cost' => 60]);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 50,
            'reorder_level' => 10,
            'location' => 'Main Warehouse'
        ]);
        
        // Create orders in current period
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 200,
            'status' => 'completed',
            'order_type' => 'online',
            'created_at' => now()
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100
        ]);
        
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 200,
            'status' => 'completed',
            'payment_method' => 'gcash'
        ]);
        
        // Act: Get the dashboard
        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'month']));
        
        // Assert: Response is successful
        $response->assertStatus(200);
        $analytics = $response->viewData('analytics');
        
        // Property: Revenue should be greater than 0
        $this->assertGreaterThan(0, $analytics['revenue']['total'], "Revenue should be greater than 0 with orders");
        
        // Property: Total orders should be greater than 0
        $this->assertGreaterThan(0, $analytics['order_metrics']['total_orders'], "Total orders should be greater than 0");
        
        // Property: AOV should be greater than 0
        $this->assertGreaterThan(0, $analytics['order_metrics']['avg_order_value'], "AOV should be greater than 0 with orders");
        
        // Property: Gross profit should be calculated
        $this->assertIsNumeric($analytics['profit_metrics']['gross_profit'], "Gross profit should be numeric");
        
        // Property: Customer count should be at least 1
        $this->assertGreaterThanOrEqual(1, $analytics['customer_metrics']['total_customers'], "Should have at least 1 customer");
    }
}
