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

    public function test_store_inline_creates_brand_with_valid_data()
    {
        $data = [
            'name' => 'Test Brand',
            'description' => 'Test brand description',
            'is_active' => true
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Test Brand',
                'slug' => 'test-brand'
            ]
        ]);
        
        $this->assertDatabaseHas('brands', [
            'name' => 'Test Brand',
            'slug' => 'test-brand'
        ]);
    }

    public function test_store_inline_validates_missing_required_fields()
    {
        $data = [
            'description' => 'Test description without name'
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), $data);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_store_inline_handles_duplicate_name()
    {
        // Create existing brand
        Brand::factory()->create(['name' => 'Existing Brand']);

        $data = [
            'name' => 'Existing Brand',
            'description' => 'Duplicate name test'
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.brands.store-inline'), $data);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonValidationErrors(['name']);
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
}
