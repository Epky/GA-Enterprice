<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class StaffCategoryControllerTest extends TestCase
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

    public function test_store_inline_creates_category_with_valid_data()
    {
        $data = [
            'name' => 'Test Category',
            'description' => 'Test description',
            'is_active' => true
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Test Category',
                'slug' => 'test-category'
            ]
        ]);
        
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);
    }

    public function test_store_inline_validates_missing_required_fields()
    {
        $data = [
            'description' => 'Test description without name'
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), $data);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_store_inline_handles_duplicate_name()
    {
        // Create existing category
        Category::factory()->create(['name' => 'Existing Category']);

        $data = [
            'name' => 'Existing Category',
            'description' => 'Duplicate name test'
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), $data);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_store_inline_generates_unique_slug()
    {
        // Create category with name 'Unique Name'
        Category::factory()->create(['name' => 'Unique Name', 'slug' => 'unique-name']);

        // Create another category with different name but similar slug potential
        $data = [
            'name' => 'Another Unique Name',
            'description' => 'Different category'
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.categories.store-inline'), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        
        // Verify unique slug was generated
        $this->assertDatabaseHas('categories', [
            'name' => 'Another Unique Name'
        ]);
        
        $category = Category::where('name', 'Another Unique Name')->first();
        $this->assertNotEquals('unique-name', $category->slug);
    }

    public function test_get_active_returns_only_active_categories()
    {
        // Clear cache before test
        Cache::forget('categories.active');
        
        // Create active and inactive categories
        Category::factory()->create(['name' => 'Active Category 1', 'is_active' => true]);
        Category::factory()->create(['name' => 'Active Category 2', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactive Category', 'is_active' => false]);

        $response = $this->actingAs($this->staffUser)
            ->getJson(route('staff.categories.active'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        // Verify all returned categories are active
        foreach ($data as $category) {
            $this->assertDatabaseHas('categories', [
                'id' => $category['id'],
                'is_active' => true
            ]);
        }
    }
}
