<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 2: Clickable cards navigate to detailed pages
 * 
 * Property: For any summary card on the overview dashboard, the card should contain 
 * a valid route link to its corresponding detailed analytics page
 * 
 * Validates: Requirements 1.3
 */
class ClickableCardNavigationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all summary cards contain valid navigation links
     * 
     * @test
     */
    public function test_summary_cards_contain_valid_navigation_links()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Test with different time periods to ensure links persist period
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            // Act: Get the dashboard with the period
            $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $period]));
            
            // Assert: Response is successful
            $response->assertStatus(200);
            
            // Property: Revenue card should link to Sales & Revenue page with period
            $expectedSalesUrl = route('admin.dashboard.sales', ['period' => $period]);
            $response->assertSee($expectedSalesUrl, false);
            
            // Property: Orders card should link to Sales & Revenue page with period
            $response->assertSee($expectedSalesUrl, false);
            
            // Property: AOV card should link to Sales & Revenue page with period
            $response->assertSee($expectedSalesUrl, false);
            
            // Property: Gross Profit card should link to Sales & Revenue page with period
            $response->assertSee($expectedSalesUrl, false);
            
            // Property: Walk-in Sales card should link to Customers & Channels page with period
            $expectedCustomersUrl = route('admin.dashboard.customers', ['period' => $period]);
            $response->assertSee($expectedCustomersUrl, false);
            
            // Property: Online Sales card should link to Customers & Channels page with period
            $response->assertSee($expectedCustomersUrl, false);
            
            // Property: Total Customers card should link to Customers & Channels page with period
            $response->assertSee($expectedCustomersUrl, false);
            
            // Property: Low Stock Items card should link to Inventory Insights page with period
            $expectedInventoryUrl = route('admin.dashboard.inventory', ['period' => $period]);
            $response->assertSee($expectedInventoryUrl, false);
        }
    }
    
    /**
     * Test that clickable cards are wrapped in anchor tags
     * 
     * @test
     */
    public function test_summary_cards_are_wrapped_in_anchor_tags()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Act: Get the dashboard
        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'month']));
        
        // Assert: Response is successful
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Property: Revenue cards should be wrapped in <a> tags
        $this->assertStringContainsString('<a href="' . route('admin.dashboard.sales', ['period' => 'month']) . '"', $content);
        
        // Property: Customer cards should be wrapped in <a> tags
        $this->assertStringContainsString('<a href="' . route('admin.dashboard.customers', ['period' => 'month']) . '"', $content);
        
        // Property: Inventory cards should be wrapped in <a> tags
        $this->assertStringContainsString('<a href="' . route('admin.dashboard.inventory', ['period' => 'month']) . '"', $content);
    }
    
    /**
     * Test that cards have hover effects for clickability indication
     * 
     * @test
     */
    public function test_clickable_cards_have_hover_effects()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Act: Get the dashboard
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        // Assert: Response is successful
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Property: Clickable cards should have hover scale effect
        // This indicates to users that the cards are interactive
        $this->assertStringContainsString('hover:scale-105', $content);
        
        // Property: Clickable cards should have transition classes
        $this->assertStringContainsString('transition-all', $content);
    }
    
    /**
     * Test that all required routes exist for card navigation
     * 
     * @test
     */
    public function test_all_required_routes_exist_for_card_navigation()
    {
        // Property: Sales & Revenue route should exist
        $this->assertTrue(
            \Route::has('admin.dashboard.sales'),
            'Sales & Revenue route should exist for card navigation'
        );
        
        // Property: Customers & Channels route should exist
        $this->assertTrue(
            \Route::has('admin.dashboard.customers'),
            'Customers & Channels route should exist for card navigation'
        );
        
        // Property: Inventory Insights route should exist
        $this->assertTrue(
            \Route::has('admin.dashboard.inventory'),
            'Inventory Insights route should exist for card navigation'
        );
    }
}
