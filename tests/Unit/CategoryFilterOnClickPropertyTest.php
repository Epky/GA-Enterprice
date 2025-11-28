<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 20: Category filter on click
 * Validates: Requirements 7.3
 */
class CategoryFilterOnClickPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 20: Category filter on click
     * For any category card clicked, the resulting page should have that category's ID in the category filter parameter
     * 
     * @test
     */
    public function property_category_card_links_to_filtered_view()
    {
        // Arrange: Create category
        $category = Category::factory()->create([
            'is_active' => true,
        ]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Category card should link to filtered view
        $response->assertStatus(200);
        $response->assertSee(route('customer.dashboard', ['category' => $category->id]), false);
    }
}
