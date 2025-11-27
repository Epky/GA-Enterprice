<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 11: Product information completeness
 * 
 * Property: For any movement with an associated product, the rendered output should
 * display both the product name and SKU
 * 
 * Validates: Requirements 3.4
 */
class DashboardMovementProductInfoPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Property: Product information includes both name and SKU
     * 
     * For any movement with a product, accessing the product relationship
     * should provide both name and SKU fields.
     */
    public function test_product_information_includes_name_and_sku()
    {
        $user = User::factory()->create();

        // Test with 50 random products
        for ($i = 0; $i < 50; $i++) {
            $product = Product::factory()->create([
                'name' => 'Test Product ' . $i,
                'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            ]);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
            ]);

            // Property: Movement should have access to product
            $this->assertNotNull($movement->product);

            // Property: Product should have a name
            $this->assertNotNull($movement->product->name);
            $this->assertNotEmpty($movement->product->name);

            // Property: Product should have a SKU
            $this->assertNotNull($movement->product->sku);
            $this->assertNotEmpty($movement->product->sku);

            // Property: Product name and SKU should match what was created
            $this->assertEquals('Test Product ' . $i, $movement->product->name);
            $this->assertEquals('SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT), $movement->product->sku);
        }
    }

    /**
     * @test
     * Property: Variant information is accessible when present
     * 
     * For any movement with a variant, accessing the variant relationship
     * should provide variant name and SKU.
     */
    public function test_variant_information_is_accessible_when_present()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'name' => 'Product ' . $i,
                'sku' => 'PROD-' . $i,
            ]);

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'name' => 'Variant ' . $i,
                'sku' => 'VAR-' . $i,
            ]);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
            ]);

            // Property: Movement should have access to both product and variant
            $this->assertNotNull($movement->product);
            $this->assertNotNull($movement->variant);

            // Property: Variant should have name and SKU
            $this->assertNotNull($movement->variant->name);
            $this->assertNotEmpty($movement->variant->name);
            $this->assertNotNull($movement->variant->sku);
            $this->assertNotEmpty($movement->variant->sku);

            // Property: Both product and variant info should be accessible
            $this->assertEquals('Product ' . $i, $movement->product->name);
            $this->assertEquals('PROD-' . $i, $movement->product->sku);
            $this->assertEquals('Variant ' . $i, $movement->variant->name);
            $this->assertEquals('VAR-' . $i, $movement->variant->sku);
        }
    }

    /**
     * @test
     * Property: Movements without variants still have product information
     * 
     * For any movement without a variant, the product information should
     * still be complete and accessible.
     */
    public function test_movements_without_variants_have_complete_product_info()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            $product = Product::factory()->create([
                'name' => 'Simple Product ' . $i,
                'sku' => 'SIMPLE-' . $i,
            ]);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null, // No variant
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
            ]);

            // Property: Movement should have product but no variant
            $this->assertNotNull($movement->product);
            $this->assertNull($movement->variant);

            // Property: Product information should be complete
            $this->assertNotNull($movement->product->name);
            $this->assertNotEmpty($movement->product->name);
            $this->assertNotNull($movement->product->sku);
            $this->assertNotEmpty($movement->product->sku);
        }
    }

    /**
     * @test
     * Property: Product relationships are eager-loadable
     * 
     * For any collection of movements, product relationships should be
     * eager-loadable to avoid N+1 queries.
     */
    public function test_product_relationships_are_eager_loadable()
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(10)->create();

        // Create movements for each product
        foreach ($products as $product) {
            InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
            ]);
        }

        // Eager load products
        $movements = InventoryMovement::with('product')->get();

        // Property: All movements should have product loaded
        foreach ($movements as $movement) {
            $this->assertTrue($movement->relationLoaded('product'));
            $this->assertNotNull($movement->product);
            $this->assertNotNull($movement->product->name);
            $this->assertNotNull($movement->product->sku);
        }
    }
}
