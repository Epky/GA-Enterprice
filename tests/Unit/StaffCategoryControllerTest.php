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



    public function test_delete_inline_successfully_deletes_category_without_products()
    {
        // Create a category without products
        $category = Category::factory()->create(['name' => 'Empty Category']);

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.categories.delete-inline', $category));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Category deleted successfully.'
        ]);
        
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
        ]);
    }

    public function test_delete_inline_prevents_deletion_of_category_with_products()
    {
        // Create a category with products
        $category = Category::factory()->hasProducts(3)->create(['name' => 'Category With Products']);

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.categories.delete-inline', $category));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'product_count' => 3
        ]);
        $response->assertJsonFragment(['message' => 'Cannot delete category with 3 associated products.']);
        
        // Verify category still exists
        $this->assertDatabaseHas('categories', [
            'id' => $category->id
        ]);
    }

    public function test_delete_inline_prevents_deletion_of_category_with_children()
    {
        // Create a parent category with child categories
        $parentCategory = Category::factory()->create(['name' => 'Parent Category']);
        Category::factory()->count(2)->create(['parent_id' => $parentCategory->id]);

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.categories.delete-inline', $parentCategory));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'child_count' => 2
        ]);
        
        // Verify parent category still exists
        $this->assertDatabaseHas('categories', [
            'id' => $parentCategory->id
        ]);
    }

    public function test_delete_inline_requires_authentication()
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson(route('staff.categories.delete-inline', $category));

        $response->assertStatus(401);
    }
}
