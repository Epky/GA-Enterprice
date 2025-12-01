<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Customer\CustomerController;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

/**
 * Unit tests for CustomerController logic
 * Tests controller methods in isolation
 */
class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CustomerController();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    // ========================================
    // Task 2.1: Filter Validation Tests
    // Requirements: 3.1, 3.2, 3.3, 3.4
    // ========================================

    /** @test */
    public function it_validates_price_range_min_less_than_or_equal_to_max()
    {
        // Valid: min <= max
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 100,
                'max_price' => 500
            ]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_rejects_min_price_greater_than_max_price()
    {
        // Invalid: min > max
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 500,
                'max_price' => 100
            ]));
        
        $response->assertSessionHasErrors('price');
    }

    /** @test */
    public function it_validates_category_id_exists()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        // Valid category ID
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => $category->id]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_rejects_invalid_category_id()
    {
        // Non-existent category ID
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => 99999]));
        
        $response->assertSessionHasErrors('category');
    }

    /** @test */
    public function it_validates_brand_id_exists()
    {
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Valid brand ID
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['brand' => $brand->id]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_rejects_invalid_brand_id()
    {
        // Non-existent brand ID
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['brand' => 99999]));
        
        $response->assertSessionHasErrors('brand');
    }

    /** @test */
    public function it_validates_search_term_is_string()
    {
        // Valid search term
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => 'lipstick']));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_validates_search_term_max_length()
    {
        // Search term within limit (255 chars)
        $validSearch = str_repeat('a', 255);
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => $validSearch]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
        
        // Search term exceeds limit (256 chars)
        $invalidSearch = str_repeat('a', 256);
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => $invalidSearch]));
        
        $response->assertSessionHasErrors('search');
    }

    /** @test */
    public function it_validates_min_price_is_numeric()
    {
        // Invalid: non-numeric min_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['min_price' => 'invalid']));
        
        $response->assertSessionHasErrors('min_price');
    }

    /** @test */
    public function it_validates_max_price_is_numeric()
    {
        // Invalid: non-numeric max_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['max_price' => 'invalid']));
        
        $response->assertSessionHasErrors('max_price');
    }

    /** @test */
    public function it_validates_min_price_is_not_negative()
    {
        // Invalid: negative min_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['min_price' => -100]));
        
        $response->assertSessionHasErrors('min_price');
    }

    /** @test */
    public function it_validates_max_price_is_not_negative()
    {
        // Invalid: negative max_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['max_price' => -100]));
        
        $response->assertSessionHasErrors('max_price');
    }

    /** @test */
    public function it_accepts_zero_as_min_price()
    {
        // Valid: zero min_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['min_price' => 0]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_accepts_zero_as_max_price()
    {
        // Valid: zero max_price
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['max_price' => 0]));
        
        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    // ========================================
    // Task 2.2: Query Building Tests
    // Requirements: 3.1, 3.2, 3.3, 3.4
    // ========================================

    /** @test */
    public function it_applies_search_filter_to_product_name()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $matchingProduct = Product::factory()->create([
            'name' => 'Red Lipstick',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $nonMatchingProduct = Product::factory()->create([
            'name' => 'Blue Foundation',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => 'Lipstick']));
        
        $response->assertStatus(200);
        $response->assertSee($matchingProduct->name);
        $response->assertDontSee($nonMatchingProduct->name);
    }

    /** @test */
    public function it_applies_search_filter_to_product_description()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $matchingProduct = Product::factory()->create([
            'name' => 'Product A',
            'description' => 'This is a moisturizing cream',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $nonMatchingProduct = Product::factory()->create([
            'name' => 'Product B',
            'description' => 'This is a cleansing foam',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => 'moisturizing']));
        
        $response->assertStatus(200);
        $response->assertSee($matchingProduct->name);
        $response->assertDontSee($nonMatchingProduct->name);
    }

    /** @test */
    public function it_applies_search_filter_to_product_sku()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $matchingProduct = Product::factory()->create([
            'name' => 'Product A',
            'sku' => 'SKU-12345',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $nonMatchingProduct = Product::factory()->create([
            'name' => 'Product B',
            'sku' => 'SKU-67890',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['search' => '12345']));
        
        $response->assertStatus(200);
        $response->assertSee($matchingProduct->name);
        $response->assertDontSee($nonMatchingProduct->name);
    }

    /** @test */
    public function it_applies_category_filter()
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'category_id' => $category1->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category2->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => $category1->id]));
        
        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_applies_brand_filter()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand1 = Brand::factory()->create(['is_active' => true]);
        $brand2 = Brand::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand1->id,
            'status' => 'active'
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
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
    public function it_applies_min_price_filter()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'base_price' => 50,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'base_price' => 500,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['min_price' => 100]));
        
        $response->assertStatus(200);
        $response->assertDontSee($cheapProduct->name);
        $response->assertSee($expensiveProduct->name);
    }

    /** @test */
    public function it_applies_max_price_filter()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'base_price' => 50,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'base_price' => 500,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['max_price' => 100]));
        
        $response->assertStatus(200);
        $response->assertSee($cheapProduct->name);
        $response->assertDontSee($expensiveProduct->name);
    }

    /** @test */
    public function it_applies_price_range_filter()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'base_price' => 50,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $midProduct = Product::factory()->create([
            'base_price' => 250,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'base_price' => 500,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'min_price' => 100,
                'max_price' => 400
            ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('products', function ($products) use ($cheapProduct, $midProduct, $expensiveProduct) {
            return !$products->contains($cheapProduct) &&
                   $products->contains($midProduct) &&
                   !$products->contains($expensiveProduct);
        });
    }

    /** @test */
    public function it_applies_combined_filters()
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        $brand1 = Brand::factory()->create(['is_active' => true]);
        $brand2 = Brand::factory()->create(['is_active' => true]);
        
        // This product matches all filters
        $matchingProduct = Product::factory()->create([
            'name' => 'Red Lipstick Premium Matching',
            'category_id' => $category1->id,
            'brand_id' => $brand1->id,
            'base_price' => 250,
            'status' => 'active'
        ]);
        
        // Wrong category
        $wrongCategory = Product::factory()->create([
            'name' => 'Red Lipstick Wrong Category',
            'category_id' => $category2->id,
            'brand_id' => $brand1->id,
            'base_price' => 250,
            'status' => 'active'
        ]);
        
        // Wrong brand
        $wrongBrand = Product::factory()->create([
            'name' => 'Red Lipstick Wrong Brand',
            'category_id' => $category1->id,
            'brand_id' => $brand2->id,
            'base_price' => 250,
            'status' => 'active'
        ]);
        
        // Wrong price
        $wrongPrice = Product::factory()->create([
            'name' => 'Red Lipstick Wrong Price',
            'category_id' => $category1->id,
            'brand_id' => $brand1->id,
            'base_price' => 50,
            'status' => 'active'
        ]);
        
        // Wrong search term
        $wrongSearch = Product::factory()->create([
            'name' => 'Blue Foundation',
            'category_id' => $category1->id,
            'brand_id' => $brand1->id,
            'base_price' => 250,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'search' => 'Lipstick',
                'category' => $category1->id,
                'brand' => $brand1->id,
                'min_price' => 100,
                'max_price' => 400
            ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('products', function ($products) use ($matchingProduct, $wrongCategory, $wrongBrand, $wrongPrice, $wrongSearch) {
            return $products->contains($matchingProduct) &&
                   !$products->contains($wrongCategory) &&
                   !$products->contains($wrongBrand) &&
                   !$products->contains($wrongPrice) &&
                   !$products->contains($wrongSearch);
        });
    }

    /** @test */
    public function it_only_shows_active_products()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'inactive'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertSee($activeProduct->name);
        $response->assertDontSee($inactiveProduct->name);
    }

    // ========================================
    // Task 2.3: Sorting Logic Tests
    // Requirements: 4.1, 4.2, 4.3, 4.4
    // ========================================

    /** @test */
    public function it_sorts_by_newest_first_by_default()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $oldProduct = Product::factory()->create([
            'name' => 'Old Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'created_at' => now()->subDays(10)
        ]);
        
        $newProduct = Product::factory()->create([
            'name' => 'New Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertSeeInOrder([$newProduct->name, $oldProduct->name]);
    }

    /** @test */
    public function it_sorts_by_newest_first_when_specified()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $oldProduct = Product::factory()->create([
            'name' => 'Old Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'created_at' => now()->subDays(10)
        ]);
        
        $newProduct = Product::factory()->create([
            'name' => 'New Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'newest']));
        
        $response->assertStatus(200);
        $response->assertSeeInOrder([$newProduct->name, $oldProduct->name]);
    }

    /** @test */
    public function it_sorts_by_price_low_to_high()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'name' => 'Cheap Product',
            'base_price' => 100,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Product',
            'base_price' => 500,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'price_low']));
        
        $response->assertStatus(200);
        $response->assertSeeInOrder([$cheapProduct->name, $expensiveProduct->name]);
    }

    /** @test */
    public function it_sorts_by_price_high_to_low()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'name' => 'Cheap Product',
            'base_price' => 100,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Product',
            'base_price' => 500,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'price_high']));
        
        $response->assertStatus(200);
        $response->assertSeeInOrder([$expensiveProduct->name, $cheapProduct->name]);
    }

    /** @test */
    public function it_sorts_alphabetically_by_name()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productZ = Product::factory()->create([
            'name' => 'Zebra Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $productA = Product::factory()->create([
            'name' => 'Apple Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'name']));
        
        $response->assertStatus(200);
        $response->assertSeeInOrder([$productA->name, $productZ->name]);
    }

    /** @test */
    public function it_validates_sort_parameter()
    {
        // Invalid sort parameter
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'invalid_sort']));
        
        $response->assertSessionHasErrors('sort');
    }

    /** @test */
    public function it_accepts_valid_sort_parameters()
    {
        $validSorts = ['newest', 'price_low', 'price_high', 'name'];
        
        foreach ($validSorts as $sort) {
            $response = $this->actingAs($this->user)
                ->get(route('customer.dashboard', ['sort' => $sort]));
            
            $response->assertStatus(200);
            $response->assertSessionHasNoErrors();
        }
    }

    // ========================================
    // Task 2.4: Pagination Tests
    // Requirements: 1.4, 3.5, 4.5
    // ========================================

    /** @test */
    public function it_paginates_products_with_12_items_per_page()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 25 products
        Product::factory()->count(25)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // First page should have 12 products
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('products', function ($products) {
            return $products->count() === 12;
        });
        
        // Second page should have 12 products
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['page' => 2]));
        
        $response->assertStatus(200);
        $response->assertViewHas('products', function ($products) {
            return $products->count() === 12;
        });
        
        // Third page should have 1 product (25 - 12 - 12 = 1)
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['page' => 3]));
        
        $response->assertStatus(200);
        $response->assertViewHas('products', function ($products) {
            return $products->count() === 1;
        });
    }

    /** @test */
    public function it_preserves_filters_during_pagination()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 15 products in the category
        Product::factory()->count(15)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Request page 2 with category filter
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'page' => 2
            ]));
        
        $response->assertStatus(200);
        
        // Check that pagination links contain the category filter
        $response->assertSee('category=' . $category->id, false);
    }

    /** @test */
    public function it_preserves_sort_during_pagination()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 15 products
        Product::factory()->count(15)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Request page 2 with sort
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'sort' => 'price_low',
                'page' => 2
            ]));
        
        $response->assertStatus(200);
        
        // Check that pagination links contain the sort parameter
        $response->assertSee('sort=price_low', false);
    }

    /** @test */
    public function it_preserves_multiple_parameters_during_pagination()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 15 products
        Product::factory()->count(15)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 250,
            'status' => 'active'
        ]);
        
        // Request page 2 with multiple filters
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'brand' => $brand->id,
                'min_price' => 100,
                'max_price' => 400,
                'sort' => 'name',
                'page' => 2
            ]));
        
        $response->assertStatus(200);
        
        // Check that pagination links contain all parameters
        $response->assertSee('category=' . $category->id, false);
        $response->assertSee('brand=' . $brand->id, false);
        $response->assertSee('min_price=100', false);
        $response->assertSee('max_price=400', false);
        $response->assertSee('sort=name', false);
    }

    // ========================================
    // Task 2.5: Featured Products Logic Tests
    // Requirements: 2.2, 2.4
    // ========================================

    /** @test */
    public function it_limits_featured_products_to_maximum_of_four()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 10 featured products
        Product::factory()->count(10)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts', function ($featuredProducts) {
            return $featuredProducts->count() === 4;
        });
    }

    /** @test */
    public function it_shows_all_featured_products_when_less_than_four()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 2 featured products
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts', function ($featuredProducts) {
            return $featuredProducts->count() === 2;
        });
    }

    /** @test */
    public function it_shows_no_featured_products_when_none_exist()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create only non-featured products
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts', function ($featuredProducts) {
            return $featuredProducts->count() === 0;
        });
    }

    /** @test */
    public function it_applies_category_filter_to_featured_products()
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create featured products in category 1
        Product::factory()->count(3)->create([
            'category_id' => $category1->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true
        ]);
        
        // Create featured products in category 2
        Product::factory()->count(3)->create([
            'category_id' => $category2->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['category' => $category1->id]));
        
        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts', function ($featuredProducts) use ($category1) {
            return $featuredProducts->count() === 3 && 
                   $featuredProducts->every(fn($p) => $p->category_id === $category1->id);
        });
    }

    /** @test */
    public function it_only_shows_active_featured_products()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create active featured products
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true
        ]);
        
        // Create inactive featured products
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'inactive',
            'is_featured' => true
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts', function ($featuredProducts) {
            return $featuredProducts->count() === 2 && 
                   $featuredProducts->every(fn($p) => $p->status === 'active');
        });
    }

    // ========================================
    // Task 2.6: Stock Calculation Tests
    // Requirements: 5.1, 5.2, 5.3
    // ========================================

    /** @test */
    public function it_calculates_stock_sum_across_multiple_locations()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Create inventory in multiple locations
        \App\Models\Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse A',
            'quantity_available' => 10,
            'quantity_reserved' => 5
        ]);
        
        \App\Models\Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse B',
            'quantity_available' => 20,
            'quantity_reserved' => 3
        ]);
        
        \App\Models\Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Store',
            'quantity_available' => 15,
            'quantity_reserved' => 2
        ]);
        
        $product->refresh();
        
        // Total stock = (10+5) + (20+3) + (15+2) = 55
        $this->assertEquals(55, $product->total_stock);
        
        // Available stock = 10 + 20 + 15 = 45
        $this->assertEquals(45, $product->available_stock);
    }

    /** @test */
    public function it_detects_zero_stock_correctly()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Create inventory with zero stock
        \App\Models\Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse',
            'quantity_available' => 0,
            'quantity_reserved' => 0
        ]);
        
        $product->refresh();
        
        $this->assertEquals(0, $product->total_stock);
        $this->assertEquals(0, $product->available_stock);
    }

    /** @test */
    public function it_calculates_stock_with_single_location()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Create inventory in single location
        \App\Models\Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Main Store',
            'quantity_available' => 25,
            'quantity_reserved' => 5
        ]);
        
        $product->refresh();
        
        // Total stock = 25 + 5 = 30
        $this->assertEquals(30, $product->total_stock);
        
        // Available stock = 25
        $this->assertEquals(25, $product->available_stock);
    }

    /** @test */
    public function it_handles_product_with_no_inventory_records()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // No inventory records created
        
        $this->assertEquals(0, $product->total_stock);
        $this->assertEquals(0, $product->available_stock);
    }

    /** @test */
    public function it_displays_out_of_stock_products_in_results()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $inStockProduct = Product::factory()->create([
            'name' => 'In Stock Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        \App\Models\Inventory::factory()->create([
            'product_id' => $inStockProduct->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0
        ]);
        
        $outOfStockProduct = Product::factory()->create([
            'name' => 'Out of Stock Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        \App\Models\Inventory::factory()->create([
            'product_id' => $outOfStockProduct->id,
            'quantity_available' => 0,
            'quantity_reserved' => 0
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));
        
        $response->assertStatus(200);
        // Both products should be visible
        $response->assertSee($inStockProduct->name);
        $response->assertSee($outOfStockProduct->name);
        // Out of stock badge should be present
        $response->assertSee('OUT OF STOCK');
    }
}
