<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CustomerDashboardSortTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    /** @test */
    public function it_sorts_by_newest_by_default()
    {
        // Requirements: 5.2
        $oldProduct = Product::factory()->create([
            'name' => 'Old Product',
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        $newProduct = Product::factory()->create([
            'name' => 'New Product',
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        $response->assertSeeInOrder([$newProduct->name, $oldProduct->name]);
    }

    /** @test */
    public function it_sorts_by_newest_when_specified()
    {
        // Requirements: 5.2
        $oldProduct = Product::factory()->create([
            'name' => 'Old Product',
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        $newProduct = Product::factory()->create([
            'name' => 'New Product',
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'newest']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([$newProduct->name, $oldProduct->name]);
    }

    /** @test */
    public function it_sorts_by_price_low_to_high()
    {
        // Requirements: 5.3
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Product',
            'base_price' => 1000,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $cheapProduct = Product::factory()->create([
            'name' => 'Cheap Product',
            'base_price' => 100,
            'status' => 'active',
            'is_featured' => false
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'price_low']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([$cheapProduct->name, $expensiveProduct->name]);
    }

    /** @test */
    public function it_sorts_by_price_high_to_low()
    {
        // Requirements: 5.4
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Product',
            'base_price' => 1000,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $cheapProduct = Product::factory()->create([
            'name' => 'Cheap Product',
            'base_price' => 100,
            'status' => 'active',
            'is_featured' => false
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'price_high']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([$expensiveProduct->name, $cheapProduct->name]);
    }

    /** @test */
    public function it_sorts_alphabetically_by_name()
    {
        // Requirements: 5.5
        $productZ = Product::factory()->create([
            'name' => 'Zebra Product',
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $productA = Product::factory()->create([
            'name' => 'Apple Product',
            'status' => 'active',
            'is_featured' => false
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'name']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([$productA->name, $productZ->name]);
    }

    /** @test */
    public function it_preserves_filters_when_sorting()
    {
        // Requirements: 5.6
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'name' => 'Product A',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 500,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        $product2 = Product::factory()->create([
            'name' => 'Product B',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 300,
            'status' => 'active',
            'is_featured' => false
        ]);
        
        // Product from different category should not appear
        $otherProduct = Product::factory()->create([
            'name' => 'Other Product',
            'base_price' => 400,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'category' => $category->id,
                'brand' => $brand->id,
                'sort' => 'price_low'
            ]));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertSee($product2->name);
        $response->assertDontSee($otherProduct->name);
        $response->assertSeeInOrder([$product2->name, $product1->name]);
    }

    /** @test */
    public function it_validates_invalid_sort_parameter()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', ['sort' => 'invalid_sort']));

        $response->assertSessionHasErrors('sort');
    }

    /** @test */
    public function it_applies_sort_with_search_filter()
    {
        // Requirements: 5.6
        $product1 = Product::factory()->create([
            'name' => 'Lipstick Red',
            'base_price' => 500,
            'status' => 'active'
        ]);
        
        $product2 = Product::factory()->create([
            'name' => 'Lipstick Pink',
            'base_price' => 300,
            'status' => 'active'
        ]);
        
        $product3 = Product::factory()->create([
            'name' => 'Foundation',
            'base_price' => 200,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.dashboard', [
                'search' => 'Lipstick',
                'sort' => 'price_low'
            ]));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertSee($product2->name);
        $response->assertDontSee($product3->name);
        
        // Verify sort order
        $content = $response->getContent();
        $pos1 = strpos($content, $product1->name); // Lipstick Red (₱500)
        $pos2 = strpos($content, $product2->name); // Lipstick Pink (₱300)
        $this->assertLessThan($pos2, $pos1, 'Lipstick Pink (cheaper) should appear before Lipstick Red');
    }
}
