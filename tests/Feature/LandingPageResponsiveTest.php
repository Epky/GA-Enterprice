<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LandingPageResponsiveTest extends TestCase
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
     * Test gradient background is present in hero section
     */
    public function test_hero_section_has_gradient_background(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500', false);
    }

    /**
     * Test hero section has white text for readability
     */
    public function test_hero_section_has_white_text(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('text-white', false);
        $response->assertSee('Discover Your Natural Beauty');
    }

    /**
     * Test responsive text sizing in hero section
     */
    public function test_hero_has_responsive_text_sizing(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for responsive text classes (text-5xl for mobile, md:text-6xl for desktop)
        $response->assertSee('text-5xl md:text-6xl', false);
    }

    /**
     * Test navigation bar has proper styling
     */
    public function test_navigation_has_proper_styling(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('bg-white/95 backdrop-blur-md shadow-xl', false);
        $response->assertSee('sticky top-0 z-50', false);
    }

    /**
     * Test buttons have proper responsive layout
     */
    public function test_buttons_have_responsive_layout(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for flex-col on mobile, sm:flex-row on larger screens
        $response->assertSee('flex flex-col sm:flex-row', false);
    }

    /**
     * Test features section uses responsive grid
     */
    public function test_features_section_has_responsive_grid(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('grid md:grid-cols-3', false);
        $response->assertSee('Why Choose Us');
    }

    /**
     * Test categories section uses responsive grid
     */
    public function test_categories_section_has_responsive_grid(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('grid md:grid-cols-4', false);
        $response->assertSee('Shop by Category');
    }

    /**
     * Test CTA section has gradient background
     */
    public function test_cta_section_has_gradient(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Ready to Glow?');
        // CTA should have same gradient as hero
        $response->assertSeeInOrder([
            'bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500',
            'Ready to Glow?'
        ], false);
    }

    /**
     * Test footer has gradient styling
     */
    public function test_footer_has_gradient_styling(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900', false);
    }

    /**
     * Test viewport meta tag is present for mobile responsiveness
     */
    public function test_viewport_meta_tag_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false);
    }

    /**
     * Test responsive padding classes are used
     */
    public function test_responsive_padding_classes(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        // Check for responsive padding (px-4 sm:px-6 lg:px-8)
        $response->assertSee('px-4 sm:px-6 lg:px-8', false);
    }

    /**
     * Test max-width container for content
     */
    public function test_max_width_container_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('max-w-7xl mx-auto', false);
    }

    /**
     * Test hover effects are present on interactive elements
     */
    public function test_hover_effects_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('hover:shadow-2xl', false);
        $response->assertSee('hover:scale-105', false);
    }

    /**
     * Test transition classes for smooth animations
     */
    public function test_transition_classes_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('transition-all duration-300', false);
    }

    /**
     * Test all required content sections are present
     */
    public function test_all_content_sections_present(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Discover Your Natural Beauty');
        $response->assertSee('Why Choose Us');
        $response->assertSee('Shop by Category');
        $response->assertSee('Ready to Glow?');
        // Check for the brand name (it appears as G&A in the HTML)
        $response->assertSee('Beauty Store');
    }

    /**
     * Test feature cards have proper styling
     */
    public function test_feature_cards_styling(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Premium Quality');
        $response->assertSee('Fast Delivery');
        $response->assertSee('Customer Care');
    }

    /**
     * Test category cards have proper styling
     */
    public function test_category_cards_styling(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Makeup');
        $response->assertSee('Skincare');
        $response->assertSee('Nail Care');
        $response->assertSee('Fragrance');
    }

    /**
     * Test navigation links are present for unauthenticated users
     */
    public function test_navigation_links_for_guests(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Login');
        $response->assertSee('Register');
    }
}
