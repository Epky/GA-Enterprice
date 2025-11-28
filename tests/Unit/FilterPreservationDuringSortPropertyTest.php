<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Feature: customer-dashboard-redesign, Property 16: Filter preservation during sort
 * Validates: Requirements 5.6
 */
class FilterPreservationDuringSortPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 16: Filter preservation during sort
     * For any combination of active filters and sort parameter, 
     * both the filters and sort should be preserved in the URL query string
     * 
     * @test
     */
    public function property_filters_and_sort_preserved_together()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(15)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Test with category and sort
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'sort' => 'price_low'
            ]));
        
        $response->assertStatus(200);
        // Check that category filter is preserved in the sort form's hidden input
        $response->assertSee('name="category" value="' . $category->id . '"', false);
        // Check that sort is selected in the dropdown
        $response->assertSee('value="price_low" selected', false);
        
        // Clean up user
        DB::table('users')->delete();
        
        // Test with brand and sort
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', [
                'brand' => $brand->id,
                'sort' => 'name'
            ]));
        
        $response->assertStatus(200);
        // Check that brand filter is preserved in the sort form's hidden input
        $response->assertSee('name="brand" value="' . $brand->id . '"', false);
        // Check that sort is selected in the dropdown
        $response->assertSee('value="name" selected', false);
    }

    /**
     * Property: Category filter preserved with sort
     * 
     * @test
     */
    public function property_category_filter_preserved_with_sort()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'sort' => 'price_low'
            ]));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('category=' . $category->id, false);
    }

    /**
     * Property: Brand filter preserved with sort
     * 
     * @test
     */
    public function property_brand_filter_preserved_with_sort()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', [
                'brand' => $brand->id,
                'sort' => 'name'
            ]));
        
        // Assert
        $response->assertStatus(200);
        // Check that brand filter is preserved in the sort form's hidden input
        $response->assertSee('name="brand" value="' . $brand->id . '"', false);
    }

    /**
     * Property: Multiple filters preserved with sort
     * 
     * @test
     */
    public function property_multiple_filters_preserved_with_sort()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        $params = [
            'category' => $category->id,
            'brand' => $brand->id,
            'sort' => 'price_high'
        ];
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', $params));
        
        // Assert
        $response->assertStatus(200);
        // Check that both filters are preserved in the sort form's hidden inputs
        $response->assertSee('name="category" value="' . $category->id . '"', false);
        $response->assertSee('name="brand" value="' . $brand->id . '"', false);
    }
}
