<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerDashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    /** @test */
    public function it_filters_products_by_category()
    {
        // Requirements: 3.2
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'category_id' => $category1->id,
            'status' => 'active'
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category2->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => $category1->id]));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_filters_products_by_brand()
    {
        // Requirements: 3.3
        $brand1 = Brand::factory()->create(['is_active' => true]);
        $brand2 = Brand::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'brand_id' => $brand1->id,
            'status' => 'active'
        ]);
        $product2 = Product::factory()->create([
            'brand_id' => $brand2->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['brand' => $brand1->id]));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_filters_products_by_price_range()
    {
        // Requirements: 3.4
        $product1 = Product::factory()->create([
            'base_price' => 100,
            'status' => 'active'
        ]);
        $product2 = Product::factory()->create([
            'base_price' => 500,
            'status' => 'active'
        ]);
        $product3 = Product::factory()->create([
            'base_price' => 1000,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 200,
                'max_price' => 800
            ]));

        $response->assertStatus(200);
        $response->assertDontSee($product1->name);
        $response->assertSee($product2->name);
        $response->assertDontSee($product3->name);
    }

    /** @test */
    public function it_filters_products_by_search_query()
    {
        // Requirements: 3.4
        $product1 = Product::factory()->create([
            'name' => 'Lipstick Red',
            'status' => 'active'
        ]);
        $product2 = Product::factory()->create([
            'name' => 'Foundation Beige',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => 'Lipstick']));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_applies_multiple_filters_simultaneously()
    {
        // Requirements: 3.2, 3.3, 3.4
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $matchingProduct = Product::factory()->create([
            'name' => 'Red Lipstick',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 500,
            'status' => 'active'
        ]);
        
        $wrongCategory = Product::factory()->create([
            'name' => 'Red Lipstick',
            'brand_id' => $brand->id,
            'base_price' => 500,
            'status' => 'active'
        ]);
        
        $wrongBrand = Product::factory()->create([
            'name' => 'Red Lipstick',
            'category_id' => $category->id,
            'base_price' => 500,
            'status' => 'active'
        ]);
        
        $wrongPrice = Product::factory()->create([
            'name' => 'Red Lipstick',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 1500,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'brand' => $brand->id,
                'min_price' => 200,
                'max_price' => 800,
                'search' => 'Lipstick'
            ]));

        $response->assertStatus(200);
        $response->assertSee($matchingProduct->name);
    }

    /** @test */
    public function it_validates_price_range_inputs()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 'invalid',
                'max_price' => 'invalid'
            ]));

        $response->assertSessionHasErrors(['min_price', 'max_price']);
    }

    /** @test */
    public function it_rejects_min_price_greater_than_max_price()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 1000,
                'max_price' => 500
            ]));

        $response->assertSessionHasErrors('price');
    }

    /** @test */
    public function it_validates_invalid_category_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => 99999]));

        $response->assertSessionHasErrors('category');
    }

    /** @test */
    public function it_validates_invalid_brand_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['brand' => 99999]));

        $response->assertSessionHasErrors('brand');
    }

    /** @test */
    public function it_handles_empty_results_gracefully()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        // No products in this category
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => $category->id]));

        $response->assertStatus(200);
        $response->assertSee('No products found');
    }
}
