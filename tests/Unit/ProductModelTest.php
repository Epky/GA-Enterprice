<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    public function test_product_belongs_to_category()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($this->category->id, $product->category->id);
    }

    public function test_product_belongs_to_brand()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertEquals($this->brand->id, $product->brand->id);
    }

    public function test_product_has_many_images()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $this->assertCount(3, $product->images);
        $this->assertInstanceOf(ProductImage::class, $product->images->first());
    }

    public function test_product_has_many_variants()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
        ]);

        $this->assertCount(2, $product->variants);
        $this->assertInstanceOf(ProductVariant::class, $product->variants->first());
    }

    public function test_product_has_inventory()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Inventory::class, $product->inventory);
    }

    public function test_product_slug_is_generated()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product Name',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->assertNotNull($product->slug);
        $this->assertStringContainsString('test-product-name', $product->slug);
    }

    public function test_active_scope_returns_only_active_products()
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'inactive',
        ]);

        $activeProducts = Product::active()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('active', $activeProducts->first()->status);
    }

    public function test_featured_scope_returns_only_featured_products()
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'is_featured' => true,
        ]);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'is_featured' => false,
        ]);

        $featuredProducts = Product::featured()->get();

        $this->assertCount(1, $featuredProducts);
        $this->assertTrue($featuredProducts->first()->is_featured);
    }

    public function test_product_can_be_soft_deleted()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
