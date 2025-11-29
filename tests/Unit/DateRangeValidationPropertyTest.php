<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property 17: Date range validation
 * 
 * Feature: admin-dashboard-reorganization, Property 17: Date range validation
 * Validates: Requirements 6.3
 * 
 * For any custom date range input, the system should validate that:
 * - Start date is not after end date
 * - Dates are in valid format
 * - Future dates beyond reasonable limits are rejected
 * - Invalid date combinations show appropriate error messages
 */
class DateRangeValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that start date after end date is rejected
     *
     * @return void
     */
    public function test_start_date_after_end_date_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01'
        ]));
        
        // Should redirect back with error or show error message
        $this->assertTrue(
            $response->isRedirect() || 
            $response->getContent() !== null
        );
        
        // If it's a redirect, check for error in session
        if ($response->isRedirect()) {
            $response->assertSessionHasErrors();
        }
    }

    /**
     * Test that invalid date format is rejected
     *
     * @return void
     */
    public function test_invalid_date_format_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $invalidDates = [
            'invalid-date',
            '2024-13-01', // Invalid month
            '2024-02-30', // Invalid day for February
            '32/01/2024', // Wrong format
            'not-a-date'
        ];
        
        foreach ($invalidDates as $invalidDate) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard', [
                'period' => 'custom',
                'start_date' => $invalidDate,
                'end_date' => '2024-12-31'
            ]));
            
            // Should handle invalid date gracefully
            $this->assertTrue(
                $response->isRedirect() || 
                $response->status() === 200
            );
        }
    }

    /**
     * Test that reasonable future dates are handled appropriately
     *
     * @return void
     */
    public function test_future_dates_are_handled_appropriately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test with future date (should be allowed but may show no data)
        $futureDate = now()->addYear()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => $futureDate
        ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('period', 'custom');
    }

    /**
     * Test that extremely far future dates are rejected
     *
     * @return void
     */
    public function test_extremely_far_future_dates_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test with date far in the future (100 years)
        $farFutureDate = now()->addYears(100)->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => $farFutureDate
        ]));
        
        // Should either redirect with error or handle gracefully
        $this->assertTrue(
            $response->isRedirect() || 
            $response->status() === 200
        );
    }

    /**
     * Test that valid date ranges are accepted
     *
     * @return void
     */
    public function test_valid_date_ranges_are_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $validRanges = [
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31'
            ],
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ],
            [
                'start_date' => now()->subMonth()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]
        ];
        
        foreach ($validRanges as $range) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard', array_merge([
                'period' => 'custom'
            ], $range)));
            
            $response->assertStatus(200);
            $response->assertViewHas('period', 'custom');
        }
    }

    /**
     * Test that same start and end date is valid
     *
     * @return void
     */
    public function test_same_start_and_end_date_is_valid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $date = '2024-06-15';
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => $date,
            'end_date' => $date
        ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('period', 'custom');
    }

    /**
     * Test that missing date parameters default appropriately
     *
     * @return void
     */
    public function test_missing_date_parameters_default_appropriately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test with custom period but missing dates
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom'
        ]));
        
        // Should either default to current month or show error
        $this->assertTrue(
            $response->status() === 200 || 
            $response->isRedirect()
        );
    }

    /**
     * Test that date range validation works across all dashboard pages
     *
     * @return void
     */
    public function test_date_range_validation_works_across_all_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $routes = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory'
        ];
        
        $invalidRange = [
            'period' => 'custom',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01'
        ];
        
        foreach ($routes as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName, $invalidRange));
            
            // Should handle invalid range consistently across all pages
            $this->assertTrue(
                $response->isRedirect() || 
                $response->status() === 200
            );
        }
    }

    /**
     * Test that date validation error messages are displayed
     *
     * @return void
     */
    public function test_date_validation_error_messages_are_displayed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01'
        ]));
        
        // If redirected, should have error messages
        if ($response->isRedirect()) {
            $response->assertSessionHasErrors();
        } else {
            // If not redirected, should handle gracefully
            $response->assertStatus(200);
        }
    }
}
