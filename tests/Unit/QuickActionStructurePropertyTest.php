<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 24: Quick action structure
 * Validates: Requirements 8.4
 */
class QuickActionStructurePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 24: Quick action structure
     * For any quick action card, it should contain both an icon element and descriptive text
     * 
     * @test
     */
    public function property_quick_action_cards_contain_icon_and_text()
    {
        // Run the test multiple times to ensure consistency
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a customer user
            $user = User::factory()->create(['role' => 'customer']);
            
            // Act: Render the dashboard view
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            $content = $response->getContent();
            
            // Assert: Check that each quick action has both icon (SVG) and text
            $response->assertStatus(200);
            
            // My Orders - should have icon and text
            $this->assertStringContainsString('My Orders', $content);
            $this->assertStringContainsString('Track your purchases', $content);
            
            // Wishlist - should have icon and text
            $this->assertStringContainsString('Wishlist', $content);
            $this->assertStringContainsString('Save your favorites', $content);
            
            // Account Settings - should have icon and text
            $this->assertStringContainsString('Account Settings', $content);
            $this->assertStringContainsString('Manage your profile', $content);
            
            // Check that SVG icons are present (at least 3 for the quick actions)
            $svgCount = substr_count($content, '<svg');
            $this->assertGreaterThanOrEqual(3, $svgCount, 'Should have at least 3 SVG icons for quick actions');
        }
    }

    /**
     * Property: Each quick action card should have an SVG icon
     * 
     * @test
     */
    public function property_each_quick_action_has_svg_icon()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'customer']);
        
        // Act
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        $content = $response->getContent();
        
        // Assert: Extract the quick actions section
        preg_match('/<!-- Quick Actions -->.*?<!-- Featured Products -->/s', $content, $matches);
        
        if (!empty($matches[0])) {
            $quickActionsSection = $matches[0];
            
            // Count SVG elements in quick actions section
            $svgCount = substr_count($quickActionsSection, '<svg');
            
            // Should have exactly 3 SVG icons (one for each quick action)
            $this->assertEquals(3, $svgCount, 'Quick actions section should have exactly 3 SVG icons');
            
            // Each SVG should have proper classes for sizing
            $this->assertStringContainsString('h-12 w-12', $quickActionsSection);
        }
    }

    /**
     * Property: Each quick action should have both a heading and descriptive text
     * 
     * @test
     */
    public function property_each_quick_action_has_heading_and_description()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'customer']);
        
        // Act
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        $content = $response->getContent();
        
        // Assert: Check for heading and description pairs
        $expectedPairs = [
            ['My Orders', 'Track your purchases'],
            ['Wishlist', 'Save your favorites'],
            ['Account Settings', 'Manage your profile'],
        ];
        
        foreach ($expectedPairs as [$heading, $description]) {
            $this->assertStringContainsString($heading, $content);
            $this->assertStringContainsString($description, $content);
            
            // Check that heading appears before description in the HTML
            $headingPos = strpos($content, $heading);
            $descPos = strpos($content, $description);
            
            $this->assertNotFalse($headingPos, "Heading '$heading' should be present");
            $this->assertNotFalse($descPos, "Description '$description' should be present");
            $this->assertLessThan($descPos, $headingPos, "Heading should appear before description");
        }
    }

    /**
     * Property: Quick actions section should have proper grid layout classes
     * 
     * @test
     */
    public function property_quick_actions_have_responsive_grid_layout()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'customer']);
        
        // Act
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        $content = $response->getContent();
        
        // Assert: Check for responsive grid classes
        preg_match('/<!-- Quick Actions -->.*?<!-- Featured Products -->/s', $content, $matches);
        
        if (!empty($matches[0])) {
            $quickActionsSection = $matches[0];
            
            // Should have grid layout with responsive columns
            $this->assertStringContainsString('grid', $quickActionsSection);
            $this->assertMatchesRegularExpression('/grid-cols-1/', $quickActionsSection);
            $this->assertMatchesRegularExpression('/md:grid-cols-3/', $quickActionsSection);
        }
    }
}
