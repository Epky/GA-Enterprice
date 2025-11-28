<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsCardComponentTest extends TestCase
{
    /**
     * Test analytics card renders with pink color prop
     */
    public function test_analytics_card_renders_with_pink_color(): void
    {
        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="pink" :icon="$icon" />',
            ['icon' => $icon]
        );

        $this->assertStringContainsString('border-pink-500', $view);
        $this->assertStringContainsString('from-pink-400 to-pink-600', $view);
        $this->assertStringContainsString('from-pink-600 to-pink-700', $view);
    }

    /**
     * Test analytics card renders with purple color prop
     */
    public function test_analytics_card_renders_with_purple_color(): void
    {
        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        
        $view = $this->blade(
            '<x-analytics-card title="Orders" value="500" color="purple" :icon="$icon" />',
            ['icon' => $icon]
        );

        $this->assertStringContainsString('border-purple-500', $view);
        $this->assertStringContainsString('from-purple-400 to-purple-600', $view);
        $this->assertStringContainsString('from-purple-600 to-purple-700', $view);
    }

    /**
     * Test analytics card renders with indigo color prop
     */
    public function test_analytics_card_renders_with_indigo_color(): void
    {
        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        
        $view = $this->blade(
            '<x-analytics-card title="Customers" value="250" color="indigo" :icon="$icon" />',
            ['icon' => $icon]
        );

        $this->assertStringContainsString('border-indigo-500', $view);
        $this->assertStringContainsString('from-indigo-400 to-indigo-600', $view);
        $this->assertStringContainsString('from-indigo-600 to-indigo-700', $view);
    }

    /**
     * Test analytics card has hover effects
     */
    public function test_analytics_card_has_hover_effects(): void
    {
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="pink" />'
        );

        $this->assertStringContainsString('hover:shadow-2xl', $view);
        $this->assertStringContainsString('hover:scale-105', $view);
        $this->assertStringContainsString('transition-all duration-300', $view);
    }

    /**
     * Test analytics card has gradient left border
     */
    public function test_analytics_card_has_gradient_left_border(): void
    {
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="pink" />'
        );

        $this->assertStringContainsString('border-l-4', $view);
        $this->assertStringContainsString('rounded-xl', $view);
        $this->assertStringContainsString('shadow-lg', $view);
    }

    /**
     * Test analytics card icon has gradient background
     */
    public function test_analytics_card_icon_has_gradient_background(): void
    {
        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="pink" :icon="$icon" />',
            ['icon' => $icon]
        );

        $this->assertStringContainsString('bg-gradient-to-br', $view);
        $this->assertStringContainsString('rounded-full', $view);
        $this->assertStringContainsString('text-white', $view);
    }

    /**
     * Test analytics card value has gradient text
     */
    public function test_analytics_card_value_has_gradient_text(): void
    {
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="pink" />'
        );

        $this->assertStringContainsString('bg-clip-text', $view);
        $this->assertStringContainsString('text-transparent', $view);
        $this->assertStringContainsString('font-bold', $view);
    }

    /**
     * Test analytics card defaults to pink when no color specified
     */
    public function test_analytics_card_defaults_to_pink(): void
    {
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" />'
        );

        $this->assertStringContainsString('border-pink-500', $view);
    }

    /**
     * Test analytics card renders change indicator with cosmetics color
     */
    public function test_analytics_card_renders_change_indicator_with_cosmetics_color(): void
    {
        $view = $this->blade(
            '<x-analytics-card title="Revenue" value="1000" color="purple" change="5%" changeType="neutral" />'
        );

        $this->assertStringContainsString('text-purple-600', $view);
    }
}
