<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 21: Active categories only
 * Validates: Requirements 7.4
 */
class CategoryActiveCategoriesPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 21: Active categories only
     * For any set of categories in the database, only those with is_active=true 
     * should be displayed in the category showcase
     * 
     * @test
     */
    public function property_only_active_categories_displayed()
    {
        // Arrange: Create active and inactive categories
        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);
        
        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);
        
        // Create products for both categories
        Product::factory()->count(5)->create([
            'category_id' => $activeCategory->id,
            'status' => 'active',
        ]);
        
        Product::factory()->count(5)->create([
            'category_id' => $inactiveCategory->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Active category should be displayed
        $response->assertStatus(200);
        $response->assertSee($activeCategory->name, false);
        
        // Inactive category should NOT be displayed
        $response->assertDontSee($inactiveCategory->name, false);
    }
}
