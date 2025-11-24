<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InlineCreatorValidationTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_validates_category_name_is_required()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => '',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_category_name_minimum_length()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'A',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_category_name_maximum_length()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => str_repeat('A', 256),
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_brand_name_is_required()
    {
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => '',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_creates_category_with_valid_data()
    {
        $response = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Valid Category',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug'],
                'message',
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Valid Category',
        ]);
    }

    /** @test */
    public function it_creates_brand_with_valid_data()
    {
        $response = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Valid Brand',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug'],
                'message',
            ]);

        $this->assertDatabaseHas('brands', [
            'name' => 'Valid Brand',
        ]);
    }
}
