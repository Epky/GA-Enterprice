<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 22: Category image fallback
 * Validates: Requirements 7.5
 */
class CategoryImageFallbackPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 22: Category image fallback
     * For any category without an image_url value, the category card should display a default category icon
     * 
     * @test
     */
    public function property_category_without_image_shows_default_icon()
    {
        // Arrange: Create category without image
        $category = Category::factory()->create([
            'is_active' => true,
            'image_url' => null,
        ]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Should show default SVG icon
        $response->assertStatus(200);
        $response->assertSee('<svg', false);
        $response->assertSee('h-24 w-24 text-purple-300', false);
    }
}
