<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 1: Date range boundaries
 * Validates: Requirements 1.1
 */
class AnalyticsDateRangeBoundariesPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 1: Date range boundaries
     * For any time period (today, week, month, year), the parsed date range should have:
     * - start date <= end date
     * - start date at beginning of period (00:00:00)
     * - end date at end of period (23:59:59)
     * 
     * @test
     */
    public function property_date_range_has_valid_boundaries_for_all_periods()
    {
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            // Act: Parse the date range
            $range = $this->analyticsService->parseDateRange($period);
            
            // Assert: Start date should be before or equal to end date
            $this->assertLessThanOrEqual(
                $range['end']->timestamp,
                $range['start']->timestamp,
                "For period '{$period}', start date should be <= end date"
            );
            
            // Assert: Start date should be at the beginning of the day (00:00:00)
            $this->assertEquals(
                '00:00:00',
                $range['start']->format('H:i:s'),
                "For period '{$period}', start date should be at 00:00:00"
            );
            
            // Assert: End date should be at the end of the day (23:59:59)
            $this->assertEquals(
                '23:59:59',
                $range['end']->format('H:i:s'),
                "For period '{$period}', end date should be at 23:59:59"
            );
        }
    }

    /**
     * Property: Today period should span exactly one day
     * 
     * @test
     */
    public function property_today_period_spans_exactly_one_day()
    {
        // Act
        $range = $this->analyticsService->parseDateRange('today');
        
        // Assert: The difference should be less than 24 hours (accounting for the 23:59:59 end time)
        $diffInHours = $range['start']->diffInHours($range['end']);
        $this->assertLessThan(24, $diffInHours);
        $this->assertGreaterThanOrEqual(23, $diffInHours);
        
        // Assert: Start and end should be on the same date
        $this->assertEquals(
            $range['start']->format('Y-m-d'),
            $range['end']->format('Y-m-d'),
            "Today period should have start and end on the same date"
        );
    }

    /**
     * Property: Week period should span 7 days
     * 
     * @test
     */
    public function property_week_period_spans_seven_days()
    {
        // Act
        $range = $this->analyticsService->parseDateRange('week');
        
        // Assert: The difference should be 6 days (7 days inclusive)
        // Using floor to handle the fractional day from time components
        $diffInDays = floor($range['start']->diffInDays($range['end']));
        $this->assertEquals(6, $diffInDays, "Week period should span 6 days (7 days inclusive)");
        
        // Assert: Start should be Monday (or configured week start)
        $this->assertEquals(
            Carbon::MONDAY,
            $range['start']->dayOfWeek,
            "Week should start on Monday"
        );
        
        // Assert: End should be Sunday (or configured week end)
        $this->assertEquals(
            Carbon::SUNDAY,
            $range['end']->dayOfWeek,
            "Week should end on Sunday"
        );
    }

    /**
     * Property: Month period should span the entire month
     * 
     * @test
     */
    public function property_month_period_spans_entire_month()
    {
        // Test with multiple random months to ensure it works for different month lengths
        for ($i = 0; $i < 12; $i++) {
            // Set a specific date in a random month
            Carbon::setTestNow(Carbon::create(2024, $i + 1, 15));
            
            // Act
            $range = $this->analyticsService->parseDateRange('month');
            
            // Assert: Start should be the 1st of the month
            $this->assertEquals(
                1,
                $range['start']->day,
                "Month should start on day 1 for month " . ($i + 1)
            );
            
            // Assert: End should be the last day of the month
            $expectedLastDay = Carbon::create(2024, $i + 1, 1)->daysInMonth;
            $this->assertEquals(
                $expectedLastDay,
                $range['end']->day,
                "Month should end on last day for month " . ($i + 1)
            );
            
            // Assert: Start and end should be in the same month
            $this->assertEquals(
                $range['start']->format('Y-m'),
                $range['end']->format('Y-m'),
                "Month period should have start and end in the same month"
            );
        }
        
        // Reset test time
        Carbon::setTestNow();
    }

    /**
     * Property: Year period should span the entire year
     * 
     * @test
     */
    public function property_year_period_spans_entire_year()
    {
        // Act
        $range = $this->analyticsService->parseDateRange('year');
        
        // Assert: Start should be January 1st
        $this->assertEquals(1, $range['start']->month, "Year should start in January");
        $this->assertEquals(1, $range['start']->day, "Year should start on day 1");
        
        // Assert: End should be December 31st
        $this->assertEquals(12, $range['end']->month, "Year should end in December");
        $this->assertEquals(31, $range['end']->day, "Year should end on day 31");
        
        // Assert: Start and end should be in the same year
        $this->assertEquals(
            $range['start']->year,
            $range['end']->year,
            "Year period should have start and end in the same year"
        );
    }

    /**
     * Property: Custom period should respect provided dates
     * 
     * @test
     */
    public function property_custom_period_respects_provided_dates()
    {
        // Arrange: Generate random start and end dates
        $customStart = Carbon::create(2024, 1, 15)->startOfDay();
        $customEnd = Carbon::create(2024, 3, 20)->endOfDay();
        
        // Act
        $range = $this->analyticsService->parseDateRange('custom', $customStart, $customEnd);
        
        // Assert: Returned dates should match provided dates
        $this->assertEquals(
            $customStart->format('Y-m-d H:i:s'),
            $range['start']->format('Y-m-d H:i:s'),
            "Custom period should use provided start date"
        );
        
        $this->assertEquals(
            $customEnd->format('Y-m-d H:i:s'),
            $range['end']->format('Y-m-d H:i:s'),
            "Custom period should use provided end date"
        );
    }

    /**
     * Property: Custom period without dates should default to current month
     * 
     * @test
     */
    public function property_custom_period_defaults_to_current_month_when_no_dates_provided()
    {
        // Act
        $range = $this->analyticsService->parseDateRange('custom');
        $expectedRange = $this->analyticsService->parseDateRange('month');
        
        // Assert: Should match month period
        $this->assertEquals(
            $expectedRange['start']->format('Y-m-d'),
            $range['start']->format('Y-m-d'),
            "Custom period without dates should default to start of month"
        );
        
        $this->assertEquals(
            $expectedRange['end']->format('Y-m-d'),
            $range['end']->format('Y-m-d'),
            "Custom period without dates should default to end of month"
        );
    }

    /**
     * Property: Invalid period should default to month
     * 
     * @test
     */
    public function property_invalid_period_defaults_to_month()
    {
        // Act
        $range = $this->analyticsService->parseDateRange('invalid_period');
        $expectedRange = $this->analyticsService->parseDateRange('month');
        
        // Assert: Should match month period
        $this->assertEquals(
            $expectedRange['start']->format('Y-m-d'),
            $range['start']->format('Y-m-d'),
            "Invalid period should default to start of month"
        );
        
        $this->assertEquals(
            $expectedRange['end']->format('Y-m-d'),
            $range['end']->format('Y-m-d'),
            "Invalid period should default to end of month"
        );
    }

    /**
     * Property: Previous period calculation maintains same duration
     * 
     * @test
     */
    public function property_previous_period_maintains_same_duration()
    {
        // Test with various periods
        $testCases = [
            ['start' => Carbon::create(2024, 1, 1), 'end' => Carbon::create(2024, 1, 31)],
            ['start' => Carbon::create(2024, 2, 1), 'end' => Carbon::create(2024, 2, 29)], // Leap year
            ['start' => Carbon::create(2024, 6, 15), 'end' => Carbon::create(2024, 6, 21)], // Week
            ['start' => Carbon::create(2024, 3, 10), 'end' => Carbon::create(2024, 3, 10)], // Single day
        ];
        
        foreach ($testCases as $testCase) {
            // Act
            $previousPeriod = $this->analyticsService->getPreviousPeriod(
                $testCase['start'],
                $testCase['end']
            );
            
            // Calculate durations
            $currentDuration = $testCase['start']->diffInDays($testCase['end']);
            $previousDuration = $previousPeriod['start']->diffInDays($previousPeriod['end']);
            
            // Assert: Previous period should have the same duration
            $this->assertEquals(
                $currentDuration,
                $previousDuration,
                "Previous period should have the same duration as current period"
            );
            
            // Assert: Previous period should end just before current period starts
            $this->assertEquals(
                $testCase['start']->copy()->subDay()->format('Y-m-d'),
                $previousPeriod['end']->format('Y-m-d'),
                "Previous period should end one day before current period starts"
            );
        }
    }

    /**
     * Property: Previous period should always be before current period
     * 
     * @test
     */
    public function property_previous_period_is_always_before_current_period()
    {
        // Generate random date ranges
        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::now()->subDays(rand(1, 365));
            $endDate = $startDate->copy()->addDays(rand(1, 90));
            
            // Act
            $previousPeriod = $this->analyticsService->getPreviousPeriod($startDate, $endDate);
            
            // Assert: Previous period end should be before current period start
            $this->assertLessThan(
                $startDate->timestamp,
                $previousPeriod['end']->timestamp,
                "Previous period should end before current period starts"
            );
            
            // Assert: Previous period start should be before previous period end
            $this->assertLessThan(
                $previousPeriod['end']->timestamp,
                $previousPeriod['start']->timestamp,
                "Previous period start should be before previous period end"
            );
        }
    }
}
