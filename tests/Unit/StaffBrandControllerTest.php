<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class StaffBrandControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a staff user for authentication
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now()
        ]);
    }



    public function test_get_active_returns_only_active_brands()
    {
        // Clear cache before test
        Cache::forget('brands.active');
        
        // Create active and inactive brands
        Brand::factory()->create(['name' => 'Active Brand 1', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Active Brand 2', 'is_active' => true]);
        Brand::factory()->create(['name' => 'Inactive Brand', 'is_active' => false]);

        $response = $this->actingAs($this->staffUser)
            ->getJson(route('staff.brands.active'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        // Verify all returned brands are active
        foreach ($data as $brand) {
            $this->assertDatabaseHas('brands', [
                'id' => $brand['id'],
                'is_active' => true
            ]);
        }
    }

    public function test_delete_inline_successfully_deletes_brand_without_products()
    {
        // Create a brand without products
        $brand = Brand::factory()->create(['name' => 'Empty Brand']);

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.brands.delete-inline', $brand));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Brand deleted successfully.'
        ]);
        
        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id
        ]);
    }

    public function test_delete_inline_prevents_deletion_of_brand_with_products()
    {
        // Create a brand with products
        $brand = Brand::factory()->hasProducts(5)->create(['name' => 'Brand With Products']);

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.brands.delete-inline', $brand));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'product_count' => 5
        ]);
        $response->assertJsonFragment(['message' => 'Cannot delete brand with 5 associated products.']);
        
        // Verify brand still exists
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id
        ]);
    }

    public function test_delete_inline_requires_authentication()
    {
        $brand = Brand::factory()->create();

        $response = $this->deleteJson(route('staff.brands.delete-inline', $brand));

        $response->assertStatus(401);
    }
}
