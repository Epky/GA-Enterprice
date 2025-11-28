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
}
