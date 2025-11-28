<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 23: Quick action links
 * Validates: Requirements 8.3
 */
class QuickActionLinksPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 23: Quick action links
     * For any quick action button, the href attribute should point to the correct route 
     * (/orders, /wishlist, or /profile)
     * 
     * @test
     */
    public function property_quick_action_links_point_to_correct_routes()
    {
        // Run the test multiple times to ensure consistency
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a customer user
            $user = User::factory()->create(['role' => 'customer']);
            
            // Act: Render the dashboard view
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Check that all three quick action links are present with correct hrefs
            $response->assertStatus(200);
            
            // My Orders link
            $response->assertSee('href="/orders"', false);
            $response->assertSee('My Orders', false);
            
            // Wishlist link
            $response->assertSee('href="/wishlist"', false);
            $response->assertSee('Wishlist', false);
            
            // Account Settings link (profile)
            $response->assertSee('href="/profile"', false);
            $response->assertSee('Account Settings', false);
        }
    }

    /**
     * Property: All quick action links should be anchor tags
     * 
     * @test
     */
    public function property_quick_action_links_are_anchor_tags()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'customer']);
        
        // Act
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        
        // Assert: Each quick action should be wrapped in an anchor tag
        $content = $response->getContent();
        
        // Check that the quick actions section exists
        $this->assertStringContainsString('Quick Actions', $content);
        
        // Check that each link is an anchor tag with proper href
        $this->assertMatchesRegularExpression('/<a[^>]*href="\/orders"[^>]*>/', $content);
        $this->assertMatchesRegularExpression('/<a[^>]*href="\/wishlist"[^>]*>/', $content);
        $this->assertMatchesRegularExpression('/<a[^>]*href="\/profile"[^>]*>/', $content);
    }

    /**
     * Property: Quick action links should be clickable (have proper href attributes)
     * 
     * @test
     */
    public function property_quick_action_links_have_valid_href_attributes()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'customer']);
        
        // Act
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        $content = $response->getContent();
        
        // Assert: Extract all hrefs from quick actions section
        preg_match_all('/href="([^"]*)"/', $content, $matches);
        $hrefs = $matches[1];
        
        // Check that the expected routes are present
        $this->assertContains('/orders', $hrefs);
        $this->assertContains('/wishlist', $hrefs);
        $this->assertContains('/profile', $hrefs);
    }
}
