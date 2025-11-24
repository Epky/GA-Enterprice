<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class InlineCacheManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a staff user for testing
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@test.com'
        ]);
    }

    /** @test */
    public function storeInline_clears_category_caches_after_creation()
    {
        // Pre-populate cache
        Cache::put('categories.active', collect(['cached' => 'data']), 3600);
        Cache::put('categories.root', collect(['cached' => 'data']), 3600);
        
        // Verify cache exists
        $this->assertTrue(Cache::has('categories.active'));
        $this->assertTrue(Cache::has('categories.root'));
        
        // Create category via inline endpoint
        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), [
                'name' => 'New Test Category',
                'description' => 'Test description',
                'is_active' => true
            ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify caches were cleared
        $this->assertFalse(Cache::has('categories.active'));
        $this->assertFalse(Cache::has('categories.root'));
    }

    /** @test */
    public function storeInline_clears_brand_caches_after_creation()
    {
        // Pre-populate cache
        Cache::put('brands.active', collect(['cached' => 'data']), 3600);
        Cache::put('brands.stats', collect(['cached' => 'data']), 300);
        
        // Verify cache exists
        $this->assertTrue(Cache::has('brands.active'));
        $this->assertTrue(Cache::has('brands.stats'));
        
        // Create brand via inline endpoint
        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), [
                'name' => 'New Test Brand',
                'description' => 'Test description',
                'is_active' => true
            ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify caches were cleared
        $this->assertFalse(Cache::has('brands.active'));
        $this->assertFalse(Cache::has('brands.stats'));
    }

    /** @test */
    public function newly_created_category_appears_in_all_categories_page_immediately()
    {
        // Create category via inline endpoint
        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), [
                'name' => 'Inline Created Category',
                'description' => 'Created via inline',
                'is_active' => true
            ]);
        
        $response->assertStatus(200);
        $categoryId = $response->json('data.id');
        
        // Immediately check if it appears in the categories index page
        $indexResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.categories.index'));
        
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Inline Created Category');
        
        // Verify the category exists in database
        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'name' => 'Inline Created Category',
            'is_active' => true
        ]);
    }

    /** @test */
    public function newly_created_brand_appears_in_all_brands_page_immediately()
    {
        // Create brand via inline endpoint
        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), [
                'name' => 'Inline Created Brand',
                'description' => 'Created via inline',
                'is_active' => true
            ]);
        
        $response->assertStatus(200);
        $brandId = $response->json('data.id');
        
        // Immediately check if it appears in the brands index page
        $indexResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.brands.index'));
        
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Inline Created Brand');
        
        // Verify the brand exists in database
        $this->assertDatabaseHas('brands', [
            'id' => $brandId,
            'name' => 'Inline Created Brand',
            'is_active' => true
        ]);
    }

    /** @test */
    public function getActive_uses_cached_data_for_categories()
    {
        // Create some categories
        $category1 = Category::factory()->create(['name' => 'Category 1', 'is_active' => true]);
        $category2 = Category::factory()->create(['name' => 'Category 2', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactive Category', 'is_active' => false]);
        
        // First call should cache the data
        $response1 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $response1->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertTrue(Cache::has('categories.active'));
        
        // Second call should use cached data
        $response2 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $response2->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify only active categories are returned
        $data = $response2->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Category 1', $data[0]['name']);
        $this->assertEquals('Category 2', $data[1]['name']);
    }

    /** @test */
    public function getActive_uses_cached_data_for_brands()
    {
        // Create some brands
        $brand1 = Brand::factory()->create(['name' => 'Brand A', 'is_active' => true]);
        $brand2 = Brand::factory()->create(['name' => 'Brand B', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Inactive Brand', 'is_active' => false]);
        
        // First call should cache the data
        $response1 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));
        
        $response1->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertTrue(Cache::has('brands.active'));
        
        // Second call should use cached data
        $response2 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));
        
        $response2->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify only active brands are returned
        $data = $response2->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Brand A', $data[0]['name']);
        $this->assertEquals('Brand B', $data[1]['name']);
    }

    /** @test */
    public function dropdown_refresh_returns_newly_created_category()
    {
        // Create initial categories
        Category::factory()->create(['name' => 'Existing Category', 'is_active' => true]);
        
        // Get initial active categories
        $response1 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $initialCount = count($response1->json('data'));
        
        // Create new category via inline
        $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), [
                'name' => 'New Inline Category',
                'is_active' => true
            ]);
        
        // Get active categories again (should refresh from database since cache was cleared)
        $response2 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $newCount = count($response2->json('data'));
        
        // Verify new category is included
        $this->assertEquals($initialCount + 1, $newCount);
        
        $categoryNames = collect($response2->json('data'))->pluck('name')->toArray();
        $this->assertContains('New Inline Category', $categoryNames);
    }

    /** @test */
    public function dropdown_refresh_returns_newly_created_brand()
    {
        // Create initial brands
        Brand::factory()->create(['name' => 'Existing Brand', 'is_active' => true]);
        
        // Get initial active brands
        $response1 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));
        
        $initialCount = count($response1->json('data'));
        
        // Create new brand via inline
        $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), [
                'name' => 'New Inline Brand',
                'is_active' => true
            ]);
        
        // Get active brands again (should refresh from database since cache was cleared)
        $response2 = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));
        
        $newCount = count($response2->json('data'));
        
        // Verify new brand is included
        $this->assertEquals($initialCount + 1, $newCount);
        
        $brandNames = collect($response2->json('data'))->pluck('name')->toArray();
        $this->assertContains('New Inline Brand', $brandNames);
    }

    /** @test */
    public function cache_is_cleared_when_category_is_updated_via_standard_endpoint()
    {
        $category = Category::factory()->create(['name' => 'Original Name', 'is_active' => true]);
        
        // Pre-populate cache
        Cache::put('categories.active', collect(['cached' => 'data']), 3600);
        $this->assertTrue(Cache::has('categories.active'));
        
        // Update category
        $this->actingAs($this->staffUser)
            ->put(route('staff.categories.update', $category), [
                'name' => 'Updated Name',
                'is_active' => true
            ]);
        
        // Verify cache was cleared
        $this->assertFalse(Cache::has('categories.active'));
    }

    /** @test */
    public function cache_is_cleared_when_brand_is_updated_via_standard_endpoint()
    {
        $brand = Brand::factory()->create(['name' => 'Original Brand', 'is_active' => true]);
        
        // Pre-populate cache
        Cache::put('brands.active', collect(['cached' => 'data']), 3600);
        $this->assertTrue(Cache::has('brands.active'));
        
        // Update brand
        $this->actingAs($this->staffUser)
            ->put(route('staff.brands.update', $brand), [
                'name' => 'Updated Brand',
                'is_active' => true
            ]);
        
        // Verify cache was cleared
        $this->assertFalse(Cache::has('brands.active'));
    }

    /** @test */
    public function multiple_inline_creations_maintain_data_consistency()
    {
        // Create multiple categories in sequence
        $categoryNames = ['Category A', 'Category B', 'Category C'];
        
        foreach ($categoryNames as $name) {
            $response = $this->actingAs($this->staffUser)
                ->postJson(route('staff.categories.store-inline'), [
                    'name' => $name,
                    'is_active' => true
                ]);
            
            $response->assertStatus(200);
        }
        
        // Verify all categories exist
        foreach ($categoryNames as $name) {
            $this->assertDatabaseHas('categories', ['name' => $name]);
        }
        
        // Verify getActive returns all of them
        $response = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $returnedNames = collect($response->json('data'))->pluck('name')->toArray();
        
        foreach ($categoryNames as $name) {
            $this->assertContains($name, $returnedNames);
        }
    }

    /** @test */
    public function inactive_items_are_not_returned_by_getActive()
    {
        // Create active and inactive categories
        Category::factory()->create(['name' => 'Active Category', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactive Category', 'is_active' => false]);
        
        // Create active and inactive brands
        Brand::factory()->create(['name' => 'Active Brand', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Inactive Brand', 'is_active' => false]);
        
        // Get active categories
        $categoryResponse = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));
        
        $categoryNames = collect($categoryResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('Active Category', $categoryNames);
        $this->assertNotContains('Inactive Category', $categoryNames);
        
        // Get active brands
        $brandResponse = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));
        
        $brandNames = collect($brandResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('Active Brand', $brandNames);
        $this->assertNotContains('Inactive Brand', $brandNames);
    }
}
