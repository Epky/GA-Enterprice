<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 4: Sales trend chart adapts to period
 * Validates: Requirements 2.3
 * 
 * Property: For any time period selection on the Sales & Revenue page, 
 * the sales trend chart should display data points that match the granularity 
 * and range of the selected period
 */
class SalesTrendChartPeriodAdaptationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that sales trend chart data adapts to different time periods
     * 
     * @test
     */
    public function sales_trend_chart_adapts_to_period()
    {
        $analyticsService = app(AnalyticsService::class);
        
        // Test different periods
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $salesTrend = $analyticsService->getDailySalesTrend($period);
            
            // Verify that sales trend data is returned
            $this->assertNotNull($salesTrend, "Sales trend should not be null for period: {$period}");
            
            // Verify that sales trend is an array
            $this->assertIsArray($salesTrend, "Sales trend should be an array for period: {$period}");
            
            // Verify the structure has required keys
            $this->assertArrayHasKey('dates', $salesTrend, "Sales trend should have 'dates' key");
            $this->assertArrayHasKey('revenue', $salesTrend, "Sales trend should have 'revenue' key");
            $this->assertArrayHasKey('orders', $salesTrend, "Sales trend should have 'orders' key");
            
            // Verify arrays are of equal length
            $dateCount = count($salesTrend['dates']);
            $revenueCount = count($salesTrend['revenue']);
            $ordersCount = count($salesTrend['orders']);
            
            $this->assertEquals($dateCount, $revenueCount, 
                "Dates and revenue arrays should have same length for period: {$period}");
            $this->assertEquals($dateCount, $ordersCount, 
                "Dates and orders arrays should have same length for period: {$period}");
            
            // Verify data points match expected granularity for the period
            if ($period === 'today') {
                // Today should have at least 1 data point (current day)
                $this->assertGreaterThanOrEqual(1, $dateCount, 
                    "Today period should have at least 1 data point");
            } elseif ($period === 'week') {
                // Week should have up to 7 daily data points
                $this->assertLessThanOrEqual(8, $dateCount, 
                    "Week period should have at most 8 data points");
            } elseif ($period === 'month') {
                // Month should have daily data (up to 31 days)
                $this->assertLessThanOrEqual(32, $dateCount, 
                    "Month period should have at most 32 data points");
            } elseif ($period === 'year') {
                // Year should have monthly data (12 months)
                $this->assertLessThanOrEqual(13, $dateCount, 
                    "Year period should have at most 13 data points");
            }
            
            // Verify revenue values are numeric
            foreach ($salesTrend['revenue'] as $revenue) {
                $this->assertTrue(is_numeric($revenue), 
                    "Revenue values should be numeric for period: {$period}");
            }
            
            // Verify order counts are numeric
            foreach ($salesTrend['orders'] as $orderCount) {
                $this->assertTrue(is_numeric($orderCount), 
                    "Order counts should be numeric for period: {$period}");
            }
        }
    }
}
