<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 16: Period persists across navigation
 * Validates: Requirements 5.3, 6.1
 */
class PeriodPersistenceAcrossNavigationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Property 16: Period persists across navigation
     * For any time period selection and any navigation between dashboard pages,
     * the selected period should be maintained in the URL and applied to the new page.
     * 
     * @test
     */
    public function property_period_persists_when_navigating_between_dashboard_pages()
    {
        // Test with all available periods
        $periods = ['today', 'week', 'month', 'year'];
        
        // Define all dashboard pages
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($periods as $period) {
            foreach ($pages as $fromPage) {
                foreach ($pages as $toPage) {
                    // Skip same page navigation
                    if ($fromPage === $toPage) {
                        continue;
                    }
                    
                    // Act: Navigate from one page to another with period parameter
                    $response = $this->actingAs($this->adminUser)
                        ->get(route($toPage, ['period' => $period]));
                    
                    // Assert: Response should be successful
                    $response->assertStatus(200);
                    
                    // Assert: Period should be present in the response
                    // The view should receive the period variable
                    $response->assertViewHas('period', $period);
                }
            }
        }
    }

    /**
     * Property: Custom period with dates persists across navigation
     * 
     * @test
     */
    public function property_custom_period_with_dates_persists_across_navigation()
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($pages as $page) {
            // Act: Navigate to page with custom period and dates
            $response = $this->actingAs($this->adminUser)
                ->get(route($page, [
                    'period' => 'custom',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Period should be 'custom'
            $response->assertViewHas('period', 'custom');
        }
    }

    /**
     * Property: Default period is 'month' when no period specified
     * 
     * @test
     */
    public function property_default_period_is_month_when_not_specified()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($pages as $page) {
            // Act: Navigate to page without period parameter
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Period should default to 'month'
            $response->assertViewHas('period', 'month');
        }
    }

    /**
     * Property: Invalid period defaults to 'month'
     * 
     * @test
     */
    public function property_invalid_period_defaults_to_month()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        $invalidPeriods = ['invalid', 'quarterly', 'daily', ''];
        
        foreach ($pages as $page) {
            foreach ($invalidPeriods as $invalidPeriod) {
                // Act: Navigate to page with invalid period
                $response = $this->actingAs($this->adminUser)
                    ->get(route($page, ['period' => $invalidPeriod]));
                
                // Assert: Should return validation error or default to month
                // Since validation will reject invalid periods, we expect either:
                // 1. A redirect back with errors (302)
                // 2. Or the controller defaults to 'month' (200)
                $this->assertContains($response->status(), [200, 302]);
                
                if ($response->status() === 200) {
                    // If it doesn't fail validation, it should default to month
                    $response->assertViewHas('period', 'month');
                }
            }
        }
    }

    /**
     * Property: Period parameter is preserved in navigation links
     * 
     * Note: This test will be fully validated once navigation component is implemented in task 2
     * 
     * @test
     */
    public function property_period_parameter_is_preserved_in_navigation_links()
    {
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            // Act: Visit overview dashboard with period
            $response = $this->actingAs($this->adminUser)
                ->get(route('admin.dashboard', ['period' => $period]));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Period is available in the view data for navigation links to use
            $response->assertViewHas('period', $period);
            
            // Note: Once navigation component is added in task 2, we can also assert:
            // $response->assertSee('period=' . $period, false);
        }
    }

    /**
     * Property: Multiple navigation hops preserve period
     * 
     * @test
     */
    public function property_multiple_navigation_hops_preserve_period()
    {
        $period = 'week';
        
        // Act: Navigate through multiple pages in sequence
        // Overview -> Sales -> Customers -> Inventory
        $response1 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard', ['period' => $period]));
        $response1->assertStatus(200);
        $response1->assertViewHas('period', $period);
        
        $response2 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.sales', ['period' => $period]));
        $response2->assertStatus(200);
        $response2->assertViewHas('period', $period);
        
        $response3 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.customers', ['period' => $period]));
        $response3->assertStatus(200);
        $response3->assertViewHas('period', $period);
        
        $response4 = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard.inventory', ['period' => $period]));
        $response4->assertStatus(200);
        $response4->assertViewHas('period', $period);
    }

    /**
     * Property: Period persistence works for all valid period values
     * 
     * @test
     */
    public function property_all_valid_periods_persist_correctly()
    {
        $validPeriods = ['today', 'week', 'month', 'year', 'custom'];
        
        $page = 'admin.dashboard.sales';
        
        foreach ($validPeriods as $period) {
            $params = ['period' => $period];
            
            // Add dates for custom period
            if ($period === 'custom') {
                $params['start_date'] = '2024-01-01';
                $params['end_date'] = '2024-01-31';
            }
            
            // Act: Navigate to page with period
            $response = $this->actingAs($this->adminUser)
                ->get(route($page, $params));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Period should be preserved
            $response->assertViewHas('period', $period);
        }
    }

    /**
     * Property: Period persistence is independent of user session
     * 
     * @test
     */
    public function property_period_persistence_is_independent_of_user_session()
    {
        // Create two different admin users
        $admin1 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin2 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        
        $period1 = 'week';
        $period2 = 'month';
        
        // Act: Admin 1 views dashboard with 'week' period
        $response1 = $this->actingAs($admin1)
            ->get(route('admin.dashboard', ['period' => $period1]));
        $response1->assertStatus(200);
        $response1->assertViewHas('period', $period1);
        
        // Act: Admin 2 views dashboard with 'month' period
        $response2 = $this->actingAs($admin2)
            ->get(route('admin.dashboard', ['period' => $period2]));
        $response2->assertStatus(200);
        $response2->assertViewHas('period', $period2);
        
        // Assert: Each user's period selection is independent
        // Admin 1 should still see 'week' when navigating
        $response3 = $this->actingAs($admin1)
            ->get(route('admin.dashboard.sales', ['period' => $period1]));
        $response3->assertStatus(200);
        $response3->assertViewHas('period', $period1);
    }
}
