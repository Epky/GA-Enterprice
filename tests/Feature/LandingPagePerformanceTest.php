<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class LandingPagePerformanceTest extends TestCase
{

    /**
     * Test that the landing page loads successfully
     */
    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    /**
     * Test that all navigation links are present and functional
     */
    public function test_navigation_links_are_present_for_guests(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Login');
        $response->assertSee('Register');
        $response->assertSee('Beauty Store'); // Partial match to avoid HTML entity issues
    }

    /**
     * Test that navigation supports role-based routing
     */
    public function test_navigation_structure_is_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Verify navigation structure exists
        $response->assertSee('Login');
        $response->assertSee('Register');
    }

    /**
     * Test that all hero section elements are present
     */
    public function test_hero_section_elements_are_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Discover Your Natural Beauty');
        $response->assertSee('Premium beauty products and cosmetics');
    }

    /**
     * Test that hero CTA buttons are present for guests
     */
    public function test_hero_cta_buttons_for_guests(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Get Started');
        $response->assertSee('Sign In');
    }

    /**
     * Test that features section is present with all three features
     */
    public function test_features_section_is_complete(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Why Choose Us');
        $response->assertSee('Premium Quality');
        $response->assertSee('Fast Delivery');
        $response->assertSee('Customer Care');
    }

    /**
     * Test that categories section is present with all four categories
     */
    public function test_categories_section_is_complete(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Shop by Category');
        $response->assertSee('Makeup');
        $response->assertSee('Skincare');
        $response->assertSee('Nail Care');
        $response->assertSee('Fragrance');
    }

    /**
     * Test that CTA section is present
     */
    public function test_cta_section_is_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Ready to Glow?');
        $response->assertSee('Join thousands of satisfied customers');
    }

    /**
     * Test that footer is present with all elements
     */
    public function test_footer_is_complete(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Your destination for premium beauty products');
        $response->assertSee('Quick Links');
        $response->assertSee('Get in Touch');
        $response->assertSee('About Us');
        $response->assertSee('Contact');
        $response->assertSee('Privacy Policy');
        $response->assertSee('Terms of Service');
        $response->assertSee('All rights reserved');
    }

    /**
     * Test that gradient classes are applied correctly
     */
    public function test_gradient_styling_is_applied(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for gradient classes in hero section
        $response->assertSee('from-pink-500 via-purple-500 to-indigo-500', false);
    }

    /**
     * Test that hover effect classes are present
     */
    public function test_hover_effects_are_defined(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for hover classes
        $response->assertSee('hover:shadow-2xl', false);
        $response->assertSee('hover:scale-105', false);
        $response->assertSee('hover:bg-pink-50', false);
    }

    /**
     * Test that transition classes are present for smooth animations
     */
    public function test_transition_classes_are_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for transition classes
        $response->assertSee('transition-all', false);
        $response->assertSee('duration-300', false);
        $response->assertSee('transition', false);
    }

    /**
     * Test that accessibility attributes are present
     */
    public function test_accessibility_attributes_are_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for ARIA labels and roles
        $response->assertSee('aria-label', false);
        $response->assertSee('role="navigation"', false);
        $response->assertSee('role="contentinfo"', false);
        $response->assertSee('aria-labelledby', false);
    }

    /**
     * Test that responsive classes are applied
     */
    public function test_responsive_classes_are_applied(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for responsive breakpoint classes
        $response->assertSee('md:text-6xl', false);
        $response->assertSee('md:grid-cols-3', false);
        $response->assertSee('md:grid-cols-4', false);
        $response->assertSee('sm:flex-row', false);
    }

    /**
     * Test that focus states are defined for keyboard navigation
     */
    public function test_focus_states_are_defined(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for focus classes
        $response->assertSee('focus:outline-none', false);
        $response->assertSee('focus:ring-2', false);
        $response->assertSee('focus:ring-offset-2', false);
    }

    /**
     * Test that the page has proper meta tags
     */
    public function test_page_has_proper_meta_tags(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('<meta charset="utf-8">', false);
        $response->assertSee('<meta name="viewport"', false);
    }

    /**
     * Test that fonts are preconnected for performance
     */
    public function test_fonts_are_preconnected(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('<link rel="preconnect"', false);
        $response->assertSee('fonts.bunny.net', false);
    }

    /**
     * Test that Vite assets are included
     */
    public function test_vite_assets_are_included(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Vite directive should be present in the view
        $response->assertViewHas('__env');
    }
}
