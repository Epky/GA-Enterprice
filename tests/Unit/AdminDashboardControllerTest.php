<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private DashboardController $controller;
    private AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
        $this->controller = new DashboardController($this->analyticsService);
    }

    /**
     * Test that index method returns view with analytics data
     * 
     * @test
     */
    public function test_index_returns_view_with_analytics_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard', 'GET', ['period' => 'month']);
        $response = $this->controller->index($request);

        $this->assertEquals('admin.dashboard', $response->name());
        $this->assertArrayHasKey('analytics', $response->getData());
        $this->assertArrayHasKey('period', $response->getData());
        $this->assertEquals('month', $response->getData()['period']);
    }

    /**
     * Test that index method defaults to month period
     * 
     * @test
     */
    public function test_index_defaults_to_month_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard', 'GET');
        $response = $this->controller->index($request);

        $this->assertEquals('month', $response->getData()['period']);
    }

    /**
     * Test that index method handles errors gracefully
     * 
     * @test
     */
    public function test_index_handles_errors_gracefully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a mock service that throws an exception
        $mockService = $this->createMock(AnalyticsService::class);
        $mockService->method('calculateRevenue')->willThrowException(new \Exception('Test error'));
        
        $controller = new DashboardController($mockService);
        $request = Request::create('/admin/dashboard', 'GET');
        $response = $controller->index($request);

        $this->assertEquals('admin.dashboard', $response->name());
        $this->assertArrayHasKey('error', $response->getData());
        $this->assertNull($response->getData()['analytics']);
    }

    /**
     * Test that getAnalyticsData returns JSON with valid period
     * 
     * @test
     */
    public function test_get_analytics_data_returns_json_with_valid_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/analytics/data', 'GET', ['period' => 'week']);
        $response = $this->controller->getAnalyticsData($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('week', $data['period']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test that getAnalyticsData validates period parameter
     * 
     * @test
     */
    public function test_get_analytics_data_validates_period_parameter()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/analytics/data', 'GET', ['period' => 'invalid']);
        $response = $this->controller->getAnalyticsData($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('VALIDATION_ERROR', $data['error']['code']);
    }

    /**
     * Test that getAnalyticsData handles errors with proper JSON format
     * 
     * @test
     */
    public function test_get_analytics_data_handles_errors_with_json_format()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a mock service that throws an exception
        $mockService = $this->createMock(AnalyticsService::class);
        $mockService->method('calculateRevenue')->willThrowException(new \Exception('Test error'));
        
        $controller = new DashboardController($mockService);
        $request = Request::create('/admin/analytics/data', 'GET', ['period' => 'month']);
        $response = $controller->getAnalyticsData($request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('ANALYTICS_ERROR', $data['error']['code']);
    }

    /**
     * Test that exportAnalytics returns CSV response
     * 
     * @test
     */
    public function test_export_analytics_returns_csv_response()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/analytics/export', 'GET', ['period' => 'month']);
        $response = $this->controller->exportAnalytics($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test that exportAnalytics includes date range in filename
     * 
     * @test
     */
    public function test_export_analytics_includes_date_range_in_filename()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/analytics/export', 'GET', ['period' => 'month']);
        $response = $this->controller->exportAnalytics($request);

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('analytics_', $contentDisposition);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $contentDisposition);
    }

    /**
     * Test that salesRevenue method returns correct view and data
     * Validates: Requirements 2.1
     * 
     * @test
     */
    public function test_sales_revenue_returns_correct_view_and_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/sales-revenue', 'GET', ['period' => 'month']);
        $response = $this->controller->salesRevenue($request);

        $this->assertEquals('admin.sales-revenue', $response->name());
        $this->assertArrayHasKey('analytics', $response->getData());
        $this->assertArrayHasKey('period', $response->getData());
        $this->assertEquals('month', $response->getData()['period']);
        
        // Verify analytics data structure
        $analytics = $response->getData()['analytics'];
        $this->assertArrayHasKey('revenue', $analytics);
        $this->assertArrayHasKey('order_metrics', $analytics);
        $this->assertArrayHasKey('profit_metrics', $analytics);
        $this->assertArrayHasKey('sales_trend', $analytics);
        $this->assertArrayHasKey('top_products', $analytics);
        $this->assertArrayHasKey('category_breakdown', $analytics);
        $this->assertArrayHasKey('brand_breakdown', $analytics);
    }

    /**
     * Test that salesRevenue method defaults to month period
     * 
     * @test
     */
    public function test_sales_revenue_defaults_to_month_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/sales-revenue', 'GET');
        $response = $this->controller->salesRevenue($request);

        $this->assertEquals('month', $response->getData()['period']);
    }

    /**
     * Test that salesRevenue method handles errors gracefully
     * 
     * @test
     */
    public function test_sales_revenue_handles_errors_gracefully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a mock service that throws an exception
        $mockService = $this->createMock(AnalyticsService::class);
        $mockService->method('calculateRevenue')->willThrowException(new \Exception('Test error'));
        
        $controller = new DashboardController($mockService);
        $request = Request::create('/admin/dashboard/sales-revenue', 'GET');
        $response = $controller->salesRevenue($request);

        $this->assertEquals('admin.sales-revenue', $response->name());
        $this->assertArrayHasKey('error', $response->getData());
    }

    /**
     * Test that customersChannels method returns correct view and data
     * Validates: Requirements 3.1
     * 
     * @test
     */
    public function test_customers_channels_returns_correct_view_and_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/customers-channels', 'GET', ['period' => 'week']);
        $response = $this->controller->customersChannels($request);

        $this->assertEquals('admin.customers-channels', $response->name());
        $this->assertArrayHasKey('analytics', $response->getData());
        $this->assertArrayHasKey('period', $response->getData());
        $this->assertEquals('week', $response->getData()['period']);
        
        // Verify analytics data structure
        $analytics = $response->getData()['analytics'];
        $this->assertArrayHasKey('customer_metrics', $analytics);
        $this->assertArrayHasKey('channel_comparison', $analytics);
        $this->assertArrayHasKey('payment_distribution', $analytics);
    }

    /**
     * Test that customersChannels method defaults to month period
     * 
     * @test
     */
    public function test_customers_channels_defaults_to_month_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/customers-channels', 'GET');
        $response = $this->controller->customersChannels($request);

        $this->assertEquals('month', $response->getData()['period']);
    }

    /**
     * Test that customersChannels method handles errors gracefully
     * 
     * @test
     */
    public function test_customers_channels_handles_errors_gracefully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a mock service that throws an exception
        $mockService = $this->createMock(AnalyticsService::class);
        $mockService->method('getCustomerMetrics')->willThrowException(new \Exception('Test error'));
        
        $controller = new DashboardController($mockService);
        $request = Request::create('/admin/dashboard/customers-channels', 'GET');
        $response = $controller->customersChannels($request);

        $this->assertEquals('admin.customers-channels', $response->name());
        $this->assertArrayHasKey('error', $response->getData());
    }

    /**
     * Test that inventoryInsights method returns correct view and data
     * Validates: Requirements 4.1
     * 
     * @test
     */
    public function test_inventory_insights_returns_correct_view_and_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/inventory-insights', 'GET', ['period' => 'year']);
        $response = $this->controller->inventoryInsights($request);

        $this->assertEquals('admin.inventory-insights', $response->name());
        $this->assertArrayHasKey('analytics', $response->getData());
        $this->assertArrayHasKey('period', $response->getData());
        $this->assertEquals('year', $response->getData()['period']);
        
        // Verify analytics data structure
        $analytics = $response->getData()['analytics'];
        $this->assertArrayHasKey('inventory_alerts', $analytics);
        $this->assertArrayHasKey('recent_movements', $analytics);
        $this->assertArrayHasKey('revenue_by_location', $analytics);
    }

    /**
     * Test that inventoryInsights method defaults to month period
     * 
     * @test
     */
    public function test_inventory_insights_defaults_to_month_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/inventory-insights', 'GET');
        $response = $this->controller->inventoryInsights($request);

        $this->assertEquals('month', $response->getData()['period']);
    }

    /**
     * Test that inventoryInsights method handles location filter
     * 
     * @test
     */
    public function test_inventory_insights_handles_location_filter()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/inventory-insights', 'GET', [
            'period' => 'month',
            'location' => 'Main Store'
        ]);
        $response = $this->controller->inventoryInsights($request);

        $this->assertEquals('admin.inventory-insights', $response->name());
        $this->assertArrayHasKey('location', $response->getData());
        $this->assertEquals('Main Store', $response->getData()['location']);
    }

    /**
     * Test that inventoryInsights method handles errors gracefully
     * 
     * @test
     */
    public function test_inventory_insights_handles_errors_gracefully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a mock service that throws an exception
        $mockService = $this->createMock(AnalyticsService::class);
        $mockService->method('getInventoryAlerts')->willThrowException(new \Exception('Test error'));
        
        $controller = new DashboardController($mockService);
        $request = Request::create('/admin/dashboard/inventory-insights', 'GET');
        $response = $controller->inventoryInsights($request);

        $this->assertEquals('admin.inventory-insights', $response->name());
        $this->assertArrayHasKey('error', $response->getData());
    }

    /**
     * Test that all methods handle period parameter correctly
     * 
     * @test
     */
    public function test_all_methods_handle_period_parameter_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            // Test salesRevenue
            $request = Request::create('/admin/dashboard/sales-revenue', 'GET', ['period' => $period]);
            $response = $this->controller->salesRevenue($request);
            $this->assertEquals($period, $response->getData()['period']);
            
            // Test customersChannels
            $request = Request::create('/admin/dashboard/customers-channels', 'GET', ['period' => $period]);
            $response = $this->controller->customersChannels($request);
            $this->assertEquals($period, $response->getData()['period']);
            
            // Test inventoryInsights
            $request = Request::create('/admin/dashboard/inventory-insights', 'GET', ['period' => $period]);
            $response = $this->controller->inventoryInsights($request);
            $this->assertEquals($period, $response->getData()['period']);
        }
    }

    /**
     * Test that all methods validate custom date range
     * 
     * @test
     */
    public function test_all_methods_validate_custom_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Test with invalid date range (end before start)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $request = Request::create('/admin/dashboard/sales-revenue', 'GET', [
            'period' => 'custom',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01'
        ]);
        
        $this->controller->salesRevenue($request);
    }

    /**
     * Test that all methods require dates for custom period
     * 
     * @test
     */
    public function test_all_methods_require_dates_for_custom_period()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $request = Request::create('/admin/dashboard/customers-channels', 'GET', [
            'period' => 'custom'
            // Missing start_date and end_date
        ]);
        
        $this->controller->customersChannels($request);
    }

    /**
     * Test that all methods accept valid custom date range
     * 
     * @test
     */
    public function test_all_methods_accept_valid_custom_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/admin/dashboard/inventory-insights', 'GET', [
            'period' => 'custom',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ]);
        
        $response = $this->controller->inventoryInsights($request);
        
        $this->assertEquals('admin.inventory-insights', $response->name());
        $this->assertEquals('custom', $response->getData()['period']);
    }
}
