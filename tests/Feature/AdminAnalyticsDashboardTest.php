<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminAnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private User $customerUser;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    /** @test */
    public function admin_can_view_dashboard_with_analytics()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('analytics');
        
        $analytics = $response->viewData('analytics');
        $this->assertArrayHasKey('revenue', $analytics);
        $this->assertArrayHasKey('order_metrics', $analytics);
        $this->assertArrayHasKey('top_products', $analytics);
        $this->assertArrayHasKey('sales_by_category', $analytics);
        $this->assertArrayHasKey('sales_by_brand', $analytics);
        $this->assertArrayHasKey('payment_distribution', $analytics);
        $this->assertArrayHasKey('channel_comparison', $analytics);
        $this->assertArrayHasKey('sales_trend', $analytics);
        $this->assertArrayHasKey('inventory_alerts', $analytics);
        $this->assertArrayHasKey('customer_metrics', $analytics);
        $this->assertArrayHasKey('profit_metrics', $analytics);
    }

    /** @test */
    public function staff_cannot_access_admin_dashboard()
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.dashboard'));

        // Middleware redirects non-admin users
        $response->assertRedirect();
    }

    /** @test */
    public function customer_cannot_access_admin_dashboard()
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('admin.dashboard'));

        // Middleware redirects non-admin users
        $response->assertRedirect();
    }

    /** @test */
    public function guest_cannot_access_admin_dashboard()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function dashboard_displays_revenue_metrics_correctly()
    {
        // Create completed orders
        $this->createCompletedOrder(1000.00, now()->subDays(5));
        $this->createCompletedOrder(1500.00, now()->subDays(3));
        $this->createCompletedOrder(2000.00, now()->subDays(1));

        // Create pending order (should not be included in revenue)
        $this->createOrder(500.00, 'pending', now()->subDays(2));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $this->assertEquals(4500.00, $analytics['revenue']['total']);
    }

    /** @test */
    public function dashboard_filters_by_time_period()
    {
        // Create orders in different time periods
        $this->createCompletedOrder(1000.00, now()->subDays(2)); // This month
        $this->createCompletedOrder(1500.00, now()->subDays(40)); // Last month (still this year)
        $this->createCompletedOrder(2000.00, now()->subDays(400)); // Last year

        // Test "month" filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'month']));

        $response->assertStatus(200);
        $analytics = $response->viewData('analytics');
        $this->assertEquals(1000.00, $analytics['revenue']['total']);

        // Test "year" filter (includes all orders from this year)
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'year']));

        $response->assertStatus(200);
        $analytics = $response->viewData('analytics');
        // Year filter includes this month + last month (both in current year)
        $this->assertEquals(2500.00, $analytics['revenue']['total']);
    }

    /** @test */
    public function dashboard_displays_order_metrics()
    {
        // Create various orders
        $this->createCompletedOrder(1000.00, now()->subDays(1), 'walk_in');
        $this->createCompletedOrder(1500.00, now()->subDays(2), 'online');
        $this->createOrder(500.00, 'pending', now()->subDays(3), 'online');
        $this->createOrder(300.00, 'cancelled', now()->subDays(4), 'walk_in');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $orderMetrics = $analytics['order_metrics'];
        $this->assertEquals(3, $orderMetrics['total_orders']); // Excludes cancelled
        $this->assertEquals(2, $orderMetrics['completed_orders']);
        $this->assertEquals(1250.00, $orderMetrics['avg_order_value']); // 2500 / 2
    }

    /** @test */
    public function dashboard_displays_top_selling_products()
    {
        $product1 = $this->createProduct('Product 1', 100.00);
        $product2 = $this->createProduct('Product 2', 50.00);
        $product3 = $this->createProduct('Product 3', 75.00);

        // Create orders with different quantities
        $order1 = $this->createCompletedOrder(0, now()->subDays(1));
        $this->createOrderItem($order1, $product1, 10, 100.00);
        $this->createOrderItem($order1, $product2, 5, 50.00);

        $order2 = $this->createCompletedOrder(0, now()->subDays(2));
        $this->createOrderItem($order2, $product2, 8, 50.00);
        $this->createOrderItem($order2, $product3, 3, 75.00);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $topProducts = $analytics['top_products'];
        $this->assertCount(3, $topProducts);
        
        // Product 2 should be first (13 total quantity)
        $this->assertEquals($product2->id, $topProducts[0]->product_id);
        $this->assertEquals(13, $topProducts[0]->total_quantity_sold);
    }

    /** @test */
    public function dashboard_displays_category_breakdown()
    {
        $category1 = Category::factory()->create(['name' => 'Category 1', 'is_active' => true]);
        $category2 = Category::factory()->create(['name' => 'Category 2', 'is_active' => true]);

        $product1 = $this->createProduct('Product 1', 100.00, $category1);
        $product2 = $this->createProduct('Product 2', 50.00, $category2);

        $order = $this->createCompletedOrder(0, now()->subDays(1));
        $this->createOrderItem($order, $product1, 5, 100.00);
        $this->createOrderItem($order, $product2, 10, 50.00);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $categoryBreakdown = $analytics['sales_by_category'];
        $this->assertCount(2, $categoryBreakdown);
    }

    /** @test */
    public function dashboard_displays_payment_method_distribution()
    {
        $order1 = $this->createCompletedOrder(1000.00, now()->subDays(1));
        Payment::factory()->create([
            'order_id' => $order1->id,
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'payment_status' => 'completed',
        ]);

        $order2 = $this->createCompletedOrder(1500.00, now()->subDays(2));
        Payment::factory()->create([
            'order_id' => $order2->id,
            'payment_method' => 'credit_card',
            'amount' => 1500.00,
            'payment_status' => 'completed',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $paymentDistribution = $analytics['payment_distribution'];
        $this->assertCount(2, $paymentDistribution);
    }

    /** @test */
    public function dashboard_displays_channel_comparison()
    {
        $this->createCompletedOrder(1000.00, now()->subDays(1), 'walk_in');
        $this->createCompletedOrder(1500.00, now()->subDays(2), 'walk_in');
        $this->createCompletedOrder(2000.00, now()->subDays(3), 'online');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $channelComparison = $analytics['channel_comparison'];
        $this->assertArrayHasKey('walk_in', $channelComparison);
        $this->assertArrayHasKey('online', $channelComparison);
        $this->assertEquals(2500.00, $channelComparison['walk_in']['revenue']);
        $this->assertEquals(2000.00, $channelComparison['online']['revenue']);
    }

    /** @test */
    public function dashboard_displays_sales_trend_chart_data()
    {
        // Create orders on different days
        $this->createCompletedOrder(1000.00, now()->subDays(5));
        $this->createCompletedOrder(1500.00, now()->subDays(3));
        $this->createCompletedOrder(2000.00, now()->subDays(1));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $salesTrend = $analytics['sales_trend'];
        $this->assertArrayHasKey('dates', $salesTrend);
        $this->assertArrayHasKey('revenue', $salesTrend);
        $this->assertArrayHasKey('orders', $salesTrend);
        $this->assertIsArray($salesTrend['dates']);
        $this->assertIsArray($salesTrend['revenue']);
        $this->assertIsArray($salesTrend['orders']);
    }

    /** @test */
    public function dashboard_displays_inventory_alerts()
    {
        $product1 = $this->createProduct('Low Stock Product', 100.00);
        $product2 = $this->createProduct('Normal Stock Product', 50.00);

        Inventory::factory()->create([
            'product_id' => $product1->id,
            'quantity_available' => 5,
            'reorder_level' => 10,
        ]);

        Inventory::factory()->create([
            'product_id' => $product2->id,
            'quantity_available' => 50,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $inventoryAlerts = $analytics['inventory_alerts'];
        $this->assertEquals(1, $inventoryAlerts['low_stock_count']);
    }

    /** @test */
    public function dashboard_displays_customer_metrics()
    {
        // Create customers at different times
        User::factory()->create([
            'role' => 'customer',
            'created_at' => now()->subDays(2),
        ]);

        User::factory()->create([
            'role' => 'customer',
            'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $customerMetrics = $analytics['customer_metrics'];
        $this->assertArrayHasKey('total_customers', $customerMetrics);
        $this->assertArrayHasKey('new_customers', $customerMetrics);
        $this->assertEquals(3, $customerMetrics['total_customers']); // Including the one from setUp
    }

    /** @test */
    public function dashboard_displays_profit_metrics()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'base_price' => 100.00,
            'cost_price' => 60.00,
        ]);

        $order = $this->createCompletedOrder(0, now()->subDays(1));
        $this->createOrderItem($order, $product, 10, 100.00);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $profitMetrics = $analytics['profit_metrics'];
        $this->assertArrayHasKey('gross_profit', $profitMetrics);
        $this->assertArrayHasKey('profit_margin', $profitMetrics);
        $this->assertEquals(400.00, $profitMetrics['gross_profit']); // (100 - 60) * 10
    }

    /** @test */
    public function admin_can_export_analytics_to_csv()
    {
        $this->createCompletedOrder(1000.00, now()->subDays(1));
        $this->createCompletedOrder(1500.00, now()->subDays(2));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', ['period' => 'month']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Revenue', $content);
        $this->assertStringContainsString('Orders', $content);
    }

    /** @test */
    public function admin_can_get_analytics_data_via_ajax()
    {
        $this->createCompletedOrder(1000.00, now()->subDays(1));

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.analytics.data', ['period' => 'month']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'revenue',
                'order_metrics',
                'top_products',
                'sales_by_category',
                'sales_by_brand',
                'payment_distribution',
                'channel_comparison',
                'sales_trend',
                'inventory_alerts',
                'customer_metrics',
                'profit_metrics',
            ],
            'period',
        ]);
    }

    /** @test */
    public function ajax_request_returns_json_error_on_failure()
    {
        // Force an error by passing invalid period
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.analytics.data', ['period' => 'invalid_period']));

        $response->assertStatus(422); // Validation error
        $response->assertJsonStructure([
            'success',
            'error' => [
                'message',
            ],
        ]);
    }

    /** @test */
    public function dashboard_handles_empty_data_gracefully()
    {
        // No orders created
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $this->assertEquals(0, $analytics['revenue']['total']);
        $this->assertEquals(0, $analytics['order_metrics']['total_orders']);
        $this->assertEquals(0, $analytics['order_metrics']['avg_order_value']);
    }

    /** @test */
    public function dashboard_with_custom_date_range()
    {
        $this->createCompletedOrder(1000.00, Carbon::parse('2024-01-15'));
        $this->createCompletedOrder(1500.00, Carbon::parse('2024-01-20'));
        $this->createCompletedOrder(2000.00, Carbon::parse('2024-02-10'));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', [
                'period' => 'custom',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $this->assertEquals(2500.00, $analytics['revenue']['total']); // Only January orders
    }

    /** @test */
    public function dashboard_validates_custom_date_range()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', [
                'period' => 'custom',
                'start_date' => '2024-02-01',
                'end_date' => '2024-01-01', // End before start
            ]));

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function dashboard_caches_analytics_data()
    {
        $this->createCompletedOrder(1000.00, now()->subDays(1));

        // First request
        $response1 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));
        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));
        $response2->assertStatus(200);

        // Both should have same data
        $analytics1 = $response1->viewData('analytics');
        $analytics2 = $response2->viewData('analytics');
        $this->assertEquals(
            $analytics1['revenue']['total'],
            $analytics2['revenue']['total']
        );
    }

    /** @test */
    public function dashboard_excludes_cancelled_orders_from_revenue()
    {
        $this->createCompletedOrder(1000.00, now()->subDays(1));
        $this->createOrder(500.00, 'cancelled', now()->subDays(2));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $this->assertEquals(1000.00, $analytics['revenue']['total']);
    }

    /** @test */
    public function dashboard_shows_percentage_changes()
    {
        // Previous period orders
        $this->createCompletedOrder(1000.00, now()->subDays(40));
        
        // Current period orders
        $this->createCompletedOrder(1500.00, now()->subDays(5));

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'month']));

        $response->assertStatus(200);
        
        $analytics = $response->viewData('analytics');
        $this->assertArrayHasKey('change_percent', $analytics['revenue']);
        $this->assertEquals(50.0, $analytics['revenue']['change_percent']); // 50% increase
    }

    // Helper methods

    private function createProduct(string $name, float $price, ?Category $category = null): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'category_id' => $category ? $category->id : $this->category->id,
            'brand_id' => $this->brand->id,
            'base_price' => $price,
            'status' => 'active',
        ]);
    }

    private function createOrder(
        float $totalAmount,
        string $status,
        Carbon $createdAt,
        string $orderType = 'online'
    ): Order {
        return Order::factory()->create([
            'user_id' => $this->customerUser->id,
            'order_status' => $status,
            'payment_status' => $status === 'completed' ? 'paid' : 'pending',
            'order_type' => $orderType,
            'total_amount' => $totalAmount,
            'created_at' => $createdAt,
        ]);
    }

    private function createCompletedOrder(
        float $totalAmount,
        Carbon $createdAt,
        string $orderType = 'online'
    ): Order {
        return $this->createOrder($totalAmount, 'completed', $createdAt, $orderType);
    }

    private function createOrderItem(Order $order, Product $product, int $quantity, float $price): OrderItem
    {
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $quantity * $price,
        ]);

        // Update order total
        $order->total_amount += $orderItem->total_price;
        $order->save();

        return $orderItem;
    }
}
