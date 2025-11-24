<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProductService;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    public function test_product_service_can_be_instantiated()
    {
        $this->assertInstanceOf(ProductService::class, $this->productService);
    }

    public function test_can_create_product()
    {
        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'description' => 'Test description',
            'price' => 29.99,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ];

        $product = $this->productService->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('TEST-001', $product->sku);
        $this->assertDatabaseHas('products', ['sku' => 'TEST-001']);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 39.99,
        ];

        $updated = $this->productService->updateProduct($product, $updateData);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 39.99,
        ]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $result = $this->productService->deleteProduct($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_change_product_status()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        $result = $this->productService->updateProductStatus($product, 'inactive');

        $this->assertTrue($result);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'inactive',
        ]);
    }

    public function test_can_toggle_featured_status()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'is_featured' => false,
        ]);

        $result = $this->productService->toggleFeatured($product);

        $this->assertTrue($result);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_featured' => true,
        ]);
    }

    public function test_can_get_low_stock_products()
    {
        Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $lowStockProducts = $this->productService->getLowStockProducts();

        $this->assertIsObject($lowStockProducts);
    }

    public function test_can_search_products()
    {
        Product::factory()->create([
            'name' => 'Lipstick Red',
            'sku' => 'LIP-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Product::factory()->create([
            'name' => 'Foundation Beige',
            'sku' => 'FND-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $results = $this->productService->searchProducts('Lipstick');

        $this->assertGreaterThan(0, $results->count());
        $this->assertEquals('Lipstick Red', $results->first()->name);
    }

    public function test_can_bulk_update_status()
    {
        $products = Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        $productIds = $products->pluck('id')->toArray();

        $result = $this->productService->bulkUpdateStatus($productIds, 'inactive');

        $this->assertTrue($result);
        
        foreach ($productIds as $id) {
            $this->assertDatabaseHas('products', [
                'id' => $id,
                'status' => 'inactive',
            ]);
        }
    }
}
