<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 14: Navigation menu present on all pages
 * Validates: Requirements 5.1, 5.4
 */
class NavigationMenuPresencePropertyTest extends TestCase
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
     * Property 14: Navigation menu present on all pages
     * For any dashboard page (overview, sales, customers, inventory),
     * the page should display a navigation menu with links to all four dashboard sections.
     * 
     * @test
     */
    public function property_navigation_menu_is_present_on_all_dashboard_pages()
    {
        // Define all dashboard pages
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        // Define expected navigation items
        $expectedNavItems = [
            'Overview',
            'Sales & Revenue',
            'Customers & Channels',
            'Inventory Insights',
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: All navigation items should be present (use true to escape HTML entities)
            foreach ($expectedNavItems as $navItem) {
                $response->assertSee($navItem, true);
            }
            
            // Assert: Navigation component should be rendered
            $response->assertSee('role="navigation"', false);
            $response->assertSee('Dashboard navigation', false);
        }
    }

    /**
     * Property: Navigation menu contains links to all dashboard sections
     * 
     * @test
     */
    public function property_navigation_menu_contains_links_to_all_sections()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        // Expected routes in navigation
        $expectedRoutes = [
            route('admin.dashboard'),
            route('admin.dashboard.sales'),
            route('admin.dashboard.customers'),
            route('admin.dashboard.inventory'),
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: All route links should be present in the HTML
            foreach ($expectedRoutes as $expectedRoute) {
                $response->assertSee($expectedRoute, false);
            }
        }
    }

    /**
     * Property: Navigation menu is present with different time periods
     * 
     * @test
     */
    public function property_navigation_menu_is_present_with_different_periods()
    {
        $periods = ['today', 'week', 'month', 'year'];
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        $expectedNavItems = [
            'Overview',
            'Sales & Revenue',
            'Customers & Channels',
            'Inventory Insights',
        ];
        
        foreach ($periods as $period) {
            foreach ($pages as $page) {
                // Act: Visit the page with period parameter
                $response = $this->actingAs($this->adminUser)
                    ->get(route($page, ['period' => $period]));
                
                // Assert: Response should be successful
                $response->assertStatus(200);
                
                // Assert: All navigation items should be present (use true to escape HTML entities)
                foreach ($expectedNavItems as $navItem) {
                    $response->assertSee($navItem, true);
                }
            }
        }
    }

    /**
     * Property: Navigation menu includes icons for all items
     * 
     * @test
     */
    public function property_navigation_menu_includes_icons_for_all_items()
    {
        $page = 'admin.dashboard';
        
        // Act: Visit the page
        $response = $this->actingAs($this->adminUser)
            ->get(route($page));
        
        // Assert: Response should be successful
        $response->assertStatus(200);
        
        // Assert: SVG icons should be present (at least 4 for each nav item)
        // The navigation component uses SVG icons for each menu item
        $content = $response->getContent();
        $svgCount = substr_count($content, '<svg');
        
        // We expect at least 4 SVG icons for the 4 navigation items
        // (could be more due to other UI elements)
        $this->assertGreaterThanOrEqual(4, $svgCount, 'Navigation should contain SVG icons for all menu items');
    }

    /**
     * Property: Navigation menu is accessible (has proper ARIA attributes)
     * 
     * @test
     */
    public function property_navigation_menu_has_proper_accessibility_attributes()
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
            
            // Assert: Navigation has proper ARIA attributes
            $response->assertSee('role="navigation"', false);
            $response->assertSee('aria-label="Dashboard navigation"', false);
            $response->assertSee('aria-current=', false);
        }
    }

    /**
     * Property: Navigation menu is responsive (has mobile version)
     * 
     * @test
     */
    public function property_navigation_menu_has_mobile_and_desktop_versions()
    {
        $page = 'admin.dashboard';
        
        // Act: Visit the page
        $response = $this->actingAs($this->adminUser)
            ->get(route($page));
        
        // Assert: Response should be successful
        $response->assertStatus(200);
        
        // Assert: Both desktop and mobile navigation should be present
        // Desktop navigation has 'hidden md:block' class
        $response->assertSee('hidden md:block', false);
        
        // Mobile navigation has 'md:hidden' class
        $response->assertSee('md:hidden', false);
        
        // Mobile menu should have collapsible functionality (x-data for Alpine.js)
        $response->assertSee('x-data', false);
    }

    /**
     * Property: Navigation menu maintains consistent structure across all pages
     * 
     * @test
     */
    public function property_navigation_menu_has_consistent_structure_across_pages()
    {
        $pages = [
            'admin.dashboard',
            'admin.dashboard.sales',
            'admin.dashboard.customers',
            'admin.dashboard.inventory',
        ];
        
        $expectedNavItems = [
            'Overview',
            'Sales & Revenue',
            'Customers & Channels',
            'Inventory Insights',
        ];
        
        foreach ($pages as $page) {
            // Act: Visit the page
            $response = $this->actingAs($this->adminUser)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: All navigation items should be present on every page
            foreach ($expectedNavItems as $navItem) {
                $response->assertSee($navItem, true);
            }
            
            // Assert: Navigation component structure is present
            $response->assertSee('role="navigation"', false);
            $response->assertSee('aria-label="Dashboard navigation"', false);
            
            // Assert: Both desktop and mobile navigation are present
            $response->assertSee('hidden md:block', false); // Desktop nav
            $response->assertSee('md:hidden', false); // Mobile nav
        }
    }

    /**
     * Property: Navigation menu works for different admin users
     * 
     * @test
     */
    public function property_navigation_menu_is_present_for_all_admin_users()
    {
        // Create multiple admin users
        $admin1 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin2 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin3 = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        
        $admins = [$admin1, $admin2, $admin3];
        $page = 'admin.dashboard';
        
        $expectedNavItems = [
            'Overview',
            'Sales & Revenue',
            'Customers & Channels',
            'Inventory Insights',
        ];
        
        foreach ($admins as $admin) {
            // Act: Visit the page as different admin
            $response = $this->actingAs($admin)
                ->get(route($page));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: All navigation items should be present (use true to escape HTML entities)
            foreach ($expectedNavItems as $navItem) {
                $response->assertSee($navItem, true);
            }
        }
    }

    /**
     * Property: Navigation menu preserves period parameter in all links
     * 
     * @test
     */
    public function property_navigation_menu_preserves_period_in_all_links()
    {
        $periods = ['today', 'week', 'month', 'year'];
        $page = 'admin.dashboard';
        
        foreach ($periods as $period) {
            // Act: Visit the page with period parameter
            $response = $this->actingAs($this->adminUser)
                ->get(route($page, ['period' => $period]));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Period parameter should be in navigation links
            $response->assertSee('period=' . $period, false);
        }
    }
}
