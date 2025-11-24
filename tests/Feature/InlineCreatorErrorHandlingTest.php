<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InlineCreatorErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a staff user
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@test.com',
        ]);
        
        $this->actingAs($this->staffUser);
    }

    /** @test */
    public function it_returns_validation_error_for_duplicate_category_name()
    {
        // Create an existing category
        Category::factory()->create([
            'name' => 'Existing Category',
            'slug' => 'existing-category',
        ]);

        // Try to create a category with the same name
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Existing Category',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_returns_validation_error_for_duplicate_brand_name()
    {
        // Create an existing brand
        Brand::factory()->create([
            'name' => 'Existing Brand',
            'slug' => 'existing-brand',
        ]);

        // Try to create a brand with the same name
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Existing Brand',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_returns_error_for_non_ajax_request_to_category_inline()
    {
        $response = $this->post(route('staff.categories.store-inline'), [
            'name' => 'Test Category',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid request type.',
            ]);
    }

    /** @test */
    public function it_returns_error_for_non_ajax_request_to_brand_inline()
    {
        $response = $this->post(route('staff.brands.store-inline'), [
            'name' => 'Test Brand',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid request type.',
            ]);
    }

    /** @test */
    public function it_returns_success_message_with_category_data()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'New Category',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug', 'parent_id', 'is_active'],
                'message',
            ]);
    }

    /** @test */
    public function it_returns_success_message_with_brand_data()
    {
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'New Brand',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Brand created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug', 'is_active'],
                'message',
            ]);
    }

    /** @test */
    public function it_validates_category_name_length()
    {
        // Test minimum length
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'A',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test maximum length
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => str_repeat('A', 256),
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_brand_name_length()
    {
        // Test minimum length
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'A',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test maximum length
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => str_repeat('A', 256),
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_handles_missing_required_fields_for_category()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_handles_missing_required_fields_for_brand()
    {
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
