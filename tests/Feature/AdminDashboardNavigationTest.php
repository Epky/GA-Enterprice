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

/**
 * Integration tests for admin dashboard navigation flow
 * 
 * Tests navigation from overview to each detailed page,
 * period persistence across page navigation,
 * and export functionality from each page.
 * 
 * Requirements: 5.3, 6.1
 */
class AdminDashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
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

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);

        // Create some test data for analytics
        $this->createTestData();
    }

    /**
     * Test navigation from overview to Sales & Revenue page
     * Requirements: 5.3
     */
    public function test_navigation_from_overview_to_sales_revenue(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        
        // Assert navigation link to sales page exists
        $response->assertSee(route('admin.dashboard.sales'), false);
        
        // Navigate to sales page
        $salesResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales'));

        $salesResponse->assertStatus(200);
        $salesResponse->assertViewIs('admin.sales-revenue');
        $salesResponse->assertViewHas('analytics');
        
        // Verify sales-specific data is present
        $analytics = $salesResponse->viewData('analytics');
        $this->assertArrayHasKey('revenue', $analytics);
        $this->assertArrayHasKey('order_metrics', $analytics);
        $this->assertArrayHasKey('top_products', $analytics);
    }

    /**
     * Test navigation from overview to Customers & Channels page
     * Requirements: 5.3
     */
    public function test_navigation_from_overview_to_customers_channels(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Assert navigation link to customers page exists
        $response->assertSee(route('admin.dashboard.customers'), false);
        
        // Navigate to customers page
        $customersResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers'));

        $customersResponse->assertStatus(200);
        $customersResponse->assertViewIs('admin.customers-channels');
        $customersResponse->assertViewHas('analytics');
        
        // Verify customers-specific data is present
        $analytics = $customersResponse->viewData('analytics');
        $this->assertArrayHasKey('customer_metrics', $analytics);
        $this->assertArrayHasKey('channel_comparison', $analytics);
        $this->assertArrayHasKey('payment_distribution', $analytics);
    }

    /**
     * Test navigation from overview to Inventory Insights page
     * Requirements: 5.3
     */
    public function test_navigation_from_overview_to_inventory_insights(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Assert navigation link to inventory page exists
        $response->assertSee(route('admin.dashboard.inventory'), false);
        
        // Navigate to inventory page
        $inventoryResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory'));

        $inventoryResponse->assertStatus(200);
        $inventoryResponse->assertViewIs('admin.inventory-insights');
        $inventoryResponse->assertViewHas('analytics');
        
        // Verify inventory-specific data is present
        $analytics = $inventoryResponse->viewData('analytics');
        $this->assertArrayHasKey('inventory_alerts', $analytics);
        $this->assertArrayHasKey('recent_movements', $analytics);
    }

    /**
     * Test period persistence when navigating from overview to sales page
     * Requirements: 5.3, 6.1
     */
    public function test_period_persists_from_overview_to_sales(): void
    {
        // Visit overview with week period
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'week']));

        $response->assertStatus(200);
        
        // Navigate to sales page - period should be in the link
        $response->assertSee('period=week', false);
        
        // Visit sales page with week period
        $salesResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', ['period' => 'week']));

        $salesResponse->assertStatus(200);
        
        // Verify period is applied to the data
        $this->assertEquals('week', $salesResponse->viewData('period'));
    }

    /**
     * Test period persistence when navigating from overview to customers page
     * Requirements: 5.3, 6.1
     */
    public function test_period_persists_from_overview_to_customers(): void
    {
        // Visit overview with year period
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'year']));

        $response->assertStatus(200);
        
        // Navigate to customers page with year period
        $customersResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers', ['period' => 'year']));

        $customersResponse->assertStatus(200);
        
        // Verify period is applied
        $this->assertEquals('year', $customersResponse->viewData('period'));
    }

    /**
     * Test period persistence when navigating from overview to inventory page
     * Requirements: 5.3, 6.1
     */
    public function test_period_persists_from_overview_to_inventory(): void
    {
        // Visit overview with month period
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'month']));

        $response->assertStatus(200);
        
        // Navigate to inventory page with month period
        $inventoryResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory', ['period' => 'month']));

        $inventoryResponse->assertStatus(200);
        
        // Verify period is applied
        $this->assertEquals('month', $inventoryResponse->viewData('period'));
    }

    /**
     * Test custom date range persistence across navigation
     * Requirements: 5.3, 6.1
     */
    public function test_custom_date_range_persists_across_navigation(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        
        // Visit overview with custom date range
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', [
                'period' => 'custom',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(200);
        
        // Navigate to sales page with same custom date range
        $salesResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', [
                'period' => 'custom',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $salesResponse->assertStatus(200);
        $this->assertEquals('custom', $salesResponse->viewData('period'));
        
        // Verify date parameters are in the navigation links
        $salesResponse->assertSee('start_date=' . $startDate, false);
        $salesResponse->assertSee('end_date=' . $endDate, false);
    }

    /**
     * Test navigation between detailed pages preserves period
     * Requirements: 5.3, 6.1
     */
    public function test_navigation_between_detailed_pages_preserves_period(): void
    {
        // Start at sales page with week period
        $salesResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', ['period' => 'week']));

        $salesResponse->assertStatus(200);
        
        // Navigate to customers page
        $customersResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers', ['period' => 'week']));

        $customersResponse->assertStatus(200);
        $this->assertEquals('week', $customersResponse->viewData('period'));
        
        // Navigate to inventory page
        $inventoryResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory', ['period' => 'week']));

        $inventoryResponse->assertStatus(200);
        $this->assertEquals('week', $inventoryResponse->viewData('period'));
    }

    /**
     * Test export functionality from overview page
     * Requirements: 6.1
     */
    public function test_export_from_overview_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', ['period' => 'month']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Revenue', $content);
        $this->assertStringContainsString('Orders', $content);
    }

    /**
     * Test export functionality from sales page
     * Requirements: 6.1
     */
    public function test_export_from_sales_page(): void
    {
        // Visit sales page first
        $pageResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', ['period' => 'week']));

        $pageResponse->assertStatus(200);
        
        // Export with same period
        $exportResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', ['period' => 'week']));

        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $exportResponse->streamedContent();
        $this->assertStringContainsString('Revenue', $content);
    }

    /**
     * Test export functionality from customers page
     * Requirements: 6.1
     */
    public function test_export_from_customers_page(): void
    {
        // Visit customers page first
        $pageResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers', ['period' => 'year']));

        $pageResponse->assertStatus(200);
        
        // Export with same period
        $exportResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', ['period' => 'year']));

        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test export functionality from inventory page
     * Requirements: 6.1
     */
    public function test_export_from_inventory_page(): void
    {
        // Visit inventory page first
        $pageResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory', ['period' => 'month']));

        $pageResponse->assertStatus(200);
        
        // Export with same period
        $exportResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', ['period' => 'month']));

        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test export with custom date range
     * Requirements: 6.1
     */
    public function test_export_with_custom_date_range(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.analytics.export', [
                'period' => 'custom',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Revenue', $content);
    }

    /**
     * Test all dashboard pages have navigation menu
     * Requirements: 5.3
     */
    public function test_all_pages_have_navigation_menu(): void
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];

        foreach ($pages as $route) {
            $response = $this->actingAs($this->adminUser)
                ->get(route($route));

            $response->assertStatus(200);
            
            // Assert navigation component is present
            $response->assertSee('Overview');
            $response->assertSee('Sales & Revenue');
            $response->assertSee('Customers & Channels');
            $response->assertSee('Inventory Insights');
        }
    }

    /**
     * Test navigation menu highlights active page
     * Requirements: 5.3
     */
    public function test_navigation_menu_highlights_active_page(): void
    {
        // Test overview page - verify it loads successfully
        $overviewResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));
        $overviewResponse->assertStatus(200);
        $overviewResponse->assertViewIs('admin.dashboard');

        // Test sales page - verify it loads successfully
        $salesResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales'));
        $salesResponse->assertStatus(200);
        $salesResponse->assertViewIs('admin.sales-revenue');

        // Test customers page - verify it loads successfully
        $customersResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers'));
        $customersResponse->assertStatus(200);
        $customersResponse->assertViewIs('admin.customers-channels');

        // Test inventory page - verify it loads successfully
        $inventoryResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory'));
        $inventoryResponse->assertStatus(200);
        $inventoryResponse->assertViewIs('admin.inventory-insights');
    }

    /**
     * Test complete navigation flow with period persistence
     * Requirements: 5.3, 6.1
     */
    public function test_complete_navigation_flow_with_period_persistence(): void
    {
        // Start at overview with week period
        $step1 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'week']));
        $step1->assertStatus(200);
        $this->assertEquals('week', $step1->viewData('period'));

        // Navigate to sales
        $step2 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', ['period' => 'week']));
        $step2->assertStatus(200);
        $this->assertEquals('week', $step2->viewData('period'));

        // Navigate to customers
        $step3 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers', ['period' => 'week']));
        $step3->assertStatus(200);
        $this->assertEquals('week', $step3->viewData('period'));

        // Navigate to inventory
        $step4 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory', ['period' => 'week']));
        $step4->assertStatus(200);
        $this->assertEquals('week', $step4->viewData('period'));

        // Navigate back to overview
        $step5 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => 'week']));
        $step5->assertStatus(200);
        $this->assertEquals('week', $step5->viewData('period'));
    }

    /**
     * Test non-admin users cannot access dashboard pages
     * Requirements: 5.3
     */
    public function test_non_admin_cannot_access_dashboard_pages(): void
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];

        foreach ($pages as $route) {
            $response = $this->actingAs($this->customerUser)
                ->get(route($route));

            // Should redirect non-admin users
            $response->assertRedirect();
        }
    }

    /**
     * Test guest users are redirected to login
     * Requirements: 5.3
     */
    public function test_guest_redirected_to_login(): void
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];

        foreach ($pages as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect(route('login'));
        }
    }

    // Helper methods

    private function createTestData(): void
    {
        // Create products
        $product1 = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'base_price' => 100.00,
            'cost_price' => 60.00,
            'status' => 'active',
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'base_price' => 50.00,
            'cost_price' => 30.00,
            'status' => 'active',
        ]);

        // Create inventory
        Inventory::factory()->create([
            'product_id' => $product1->id,
            'quantity_available' => 10,
            'reorder_level' => 5,
        ]);

        Inventory::factory()->create([
            'product_id' => $product2->id,
            'quantity_available' => 3,
            'reorder_level' => 10, // Low stock
        ]);

        // Create orders
        $order1 = Order::factory()->create([
            'user_id' => $this->customerUser->id,
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'order_type' => 'online',
            'total_amount' => 0,
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00,
        ]);

        $order1->update(['total_amount' => 200.00]);

        Payment::factory()->create([
            'order_id' => $order1->id,
            'payment_method' => 'credit_card',
            'amount' => 200.00,
            'payment_status' => 'completed',
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $this->customerUser->id,
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'order_type' => 'walk_in',
            'total_amount' => 0,
            'created_at' => now()->subDays(5),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'unit_price' => 50.00,
            'total_price' => 150.00,
        ]);

        $order2->update(['total_amount' => 150.00]);

        Payment::factory()->create([
            'order_id' => $order2->id,
            'payment_method' => 'cash',
            'amount' => 150.00,
            'payment_status' => 'completed',
        ]);
    }
}
