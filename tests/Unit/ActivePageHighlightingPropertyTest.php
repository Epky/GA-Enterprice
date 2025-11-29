<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 15: Active page highlighted in navigation
 * Validates: Requirements 5.2
 */
class ActivePageHighlightingPropertyTest extends TestCase
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
     * Property 15: Active page highlighted in navigation
     * For any dashboard page, the navigation menu should highlight 
     * the menu item corresponding to the current page.
     * 
     * @test
     */
    public function property_active_page_is_highlighted_in_navigation()
    {
        // Define pages and their expected active states
        $pages = [
            ['route' => 'admin.dashboard', 'activeKey' => 'overview'],
            ['route' => 'admin.dashboard.sales', 'activeKey' => 'sales'],
            ['route' => 'admin.dashboard.customers', 'activeKey' => 'customers'],
            ['route' => 'admin.dashboard.inventory', 'activeKey' => 'inventory'],
        ];
        
        foreach ($pages as $pageInfo) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($pageInfo['route']));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: The active page should have the gradient background class
            // The navigation component uses 'bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white' for active items
            $response->assertSee('bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white', false);
            
            // Assert: The active page should have aria-current="page"
            $response->assertSee('aria-current="page"', false);
        }
    }

    /**
     * Property: Each page highlights exactly one navigation item
     * 
     * @test
     */
    public function property_each_page_highlights_exactly_one_navigation_item()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Count occurrences of aria-current="page" (should be exactly 2: desktop + mobile nav)
            $ariaCurrentCount = substr_count($content, 'aria-current="page"');
            
            $this->assertEquals(
                2,
                $ariaCurrentCount,
                "Page {$page} should have exactly 2 active navigation items (desktop + mobile)"
            );
        }
    }

    /**
     * Property: Active highlighting persists with period parameter
     * 
     * @test
     */
    public function property_active_highlighting_persists_with_period_parameter()
    {
        $periods = ['today', 'week', 'month', 'year'];
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($periods as $period) {
            foreach ($pages as $page) {
                // Act: Visit the page with period parameter
                $response = $this->actingAs($this->adminUser)
                    ->get(route($page, ['period' => $period]));
                
                // Assert: Response should be successful
                $response->assertStatus(200);
                
                // Assert: Active highlighting should still be present
                $response->assertSee('aria-current="page"', false);
                $response->assertSee('bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white', false);
            }
        }
    }

    /**
     * Property: Non-active pages have hover states but not active states
     * 
     * @test
     */
    public function property_non_active_pages_have_hover_states_not_active_states()
    {
        // Visit the overview page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));
        
        // Assert: Response should be successful
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Assert: Non-active items should have hover classes
        // The navigation component uses 'text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:via-purple-50 hover:to-indigo-50' for non-active items
        $this->assertStringContainsString(
            'text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:via-purple-50 hover:to-indigo-50',
            $content,
            'Non-active navigation items should have hover states'
        );
        
        // Assert: Non-active items should have aria-current="false"
        $this->assertStringContainsString(
            'aria-current="false"',
            $content,
            'Non-active navigation items should have aria-current="false"'
        );
    }

    /**
     * Property: Active highlighting is consistent across desktop and mobile navigation
     * 
     * @test
     */
    public function property_active_highlighting_consistent_across_desktop_and_mobile()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Count active state indicators (should appear in both desktop and mobile nav)
            $activeClassCount = substr_count($content, 'bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white');
            
            // Should have at least 2 occurrences (desktop + mobile)
            $this->assertGreaterThanOrEqual(
                2,
                $activeClassCount,
                "Active highlighting should appear in both desktop and mobile navigation"
            );
        }
    }

    /**
     * Property: Active page highlighting works for all admin users
     * 
     * @test
     */
    public function property_active_highlighting_works_for_all_admin_users()
    {
        // Create multiple admin users
        $admin1 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin2 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin3 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        
        $admins = [$admin1, $admin2, $admin3];
        $page = 'admin.dashboard.sales';
        
        foreach ($admins as $admin) {
            // Act: Visit the page as different admin
            $response = $this->actingAs($admin)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Active highlighting should be present
            $response->assertSee('aria-current="page"', false);
            $response->assertSee('bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white', false);
        }
    }

    /**
     * Property: Mobile navigation shows checkmark icon for active page
     * 
     * @test
     */
    public function property_mobile_navigation_shows_checkmark_for_active_page()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Assert: Mobile navigation should have checkmark SVG for active item
            // The navigation component shows a checkmark icon with path "M16.707 5.293a1 1 0 010 1.414l-8 8..."
            $this->assertStringContainsString(
                'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4',
                $content,
                'Mobile navigation should show checkmark icon for active page'
            );
        }
    }
}
