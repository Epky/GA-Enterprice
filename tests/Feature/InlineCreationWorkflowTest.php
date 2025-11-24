<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InlineCreationWorkflowTest extends TestCase
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
    public function staff_can_create_category_inline_from_product_form()
    {
        // Visit product create form
        $response = $this->get(route('staff.products.create'));
        $response->assertStatus(200);
        
        // Verify the form has category dropdown and add button
        $response->assertSee('category_id');
        $response->assertSee('Add New');
        
        // Create category via inline endpoint
        $categoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Inline Test Category',
            'description' => 'Created from product form',
            'is_active' => true,
        ]);
        
        $categoryResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug', 'parent_id', 'is_active'],
                'message',
            ]);
        
        // Verify category was created in database
        $this->assertDatabaseHas('categories', [
            'name' => 'Inline Test Category',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function staff_can_create_brand_inline_from_product_form()
    {
        // Visit product create form
        $response = $this->get(route('staff.products.create'));
        $response->assertStatus(200);
        
        // Verify the form has brand dropdown and add button
        $response->assertSee('brand_id');
        $response->assertSee('Add New');
        
        // Create brand via inline endpoint
        $brandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Inline Test Brand',
            'description' => 'Created from product form',
            'is_active' => true,
        ]);
        
        $brandResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Brand created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug', 'is_active'],
                'message',
            ]);
        
        // Verify brand was created in database
        $this->assertDatabaseHas('brands', [
            'name' => 'Inline Test Brand',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function newly_created_category_appears_in_dropdown()
    {
        // Create initial categories
        $existingCategory = Category::factory()->create([
            'name' => 'Existing Category',
            'is_active' => true,
        ]);
        
        // Get active categories before creation
        $beforeResponse = $this->getJson(route('staff.categories.active'));
        $beforeResponse->assertStatus(200);
        $beforeCount = count($beforeResponse->json('data'));
        
        // Create new category via inline
        $createResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'New Dropdown Category',
            'description' => 'Should appear in dropdown',
            'is_active' => true,
        ]);
        
        $createResponse->assertStatus(200);
        $newCategoryId = $createResponse->json('data.id');
        
        // Get active categories after creation
        $afterResponse = $this->getJson(route('staff.categories.active'));
        $afterResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $afterCount = count($afterResponse->json('data'));
        $categoryNames = collect($afterResponse->json('data'))->pluck('name')->toArray();
        
        // Verify new category appears in dropdown
        $this->assertEquals($beforeCount + 1, $afterCount);
        $this->assertContains('New Dropdown Category', $categoryNames);
        
        // Verify the response includes the new category with correct data
        $newCategory = collect($afterResponse->json('data'))
            ->firstWhere('id', $newCategoryId);
        
        $this->assertNotNull($newCategory);
        $this->assertEquals('New Dropdown Category', $newCategory['name']);
        $this->assertTrue($newCategory['is_active']);
    }

    /** @test */
    public function newly_created_brand_appears_in_dropdown()
    {
        // Create initial brands
        $existingBrand = Brand::factory()->create([
            'name' => 'Existing Brand',
            'is_active' => true,
        ]);
        
        // Get active brands before creation
        $beforeResponse = $this->getJson(route('staff.brands.active'));
        $beforeResponse->assertStatus(200);
        $beforeCount = count($beforeResponse->json('data'));
        
        // Create new brand via inline
        $createResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'New Dropdown Brand',
            'description' => 'Should appear in dropdown',
            'is_active' => true,
        ]);
        
        $createResponse->assertStatus(200);
        $newBrandId = $createResponse->json('data.id');
        
        // Get active brands after creation
        $afterResponse = $this->getJson(route('staff.brands.active'));
        $afterResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $afterCount = count($afterResponse->json('data'));
        $brandNames = collect($afterResponse->json('data'))->pluck('name')->toArray();
        
        // Verify new brand appears in dropdown
        $this->assertEquals($beforeCount + 1, $afterCount);
        $this->assertContains('New Dropdown Brand', $brandNames);
        
        // Verify the response includes the new brand with correct data
        $newBrand = collect($afterResponse->json('data'))
            ->firstWhere('id', $newBrandId);
        
        $this->assertNotNull($newBrand);
        $this->assertEquals('New Dropdown Brand', $newBrand['name']);
        $this->assertTrue($newBrand['is_active']);
    }

    /** @test */
    public function product_form_data_is_maintained_during_inline_creation()
    {
        // Create existing category and brand for the product form
        $category = Category::factory()->create(['name' => 'Test Category', 'is_active' => true]);
        $brand = Brand::factory()->create(['name' => 'Test Brand', 'is_active' => true]);
        
        // Simulate product form data in session
        $productFormData = [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'base_price' => 99.99,
            'description' => 'Test product description',
            'status' => 'active',
        ];
        
        // Store form data in session (simulating user filling out form)
        session($productFormData);
        
        // Create new category via inline while "on" product form
        $categoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Another Category',
            'is_active' => true,
        ]);
        
        $categoryResponse->assertStatus(200);
        
        // Verify session data is still intact
        $this->assertEquals('Test Product', session('name'));
        $this->assertEquals('TEST-SKU-001', session('sku'));
        $this->assertEquals(99.99, session('base_price'));
        $this->assertEquals('Test product description', session('description'));
        
        // Create new brand via inline
        $brandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Another Brand',
            'is_active' => true,
        ]);
        
        $brandResponse->assertStatus(200);
        
        // Verify session data is still intact after brand creation
        $this->assertEquals('Test Product', session('name'));
        $this->assertEquals('TEST-SKU-001', session('sku'));
    }

    /** @test */
    public function validation_error_display_in_modal()
    {
        // Test category validation errors
        $categoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => '', // Empty name should fail validation
            'description' => 'Test description',
            'is_active' => true,
        ]);
        
        $categoryResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['name']);
        
        // Test brand validation errors
        $brandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => '', // Empty name should fail validation
            'description' => 'Test description',
            'is_active' => true,
        ]);
        
        $brandResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['name']);
        
        // Test duplicate name validation
        Category::factory()->create(['name' => 'Duplicate Category']);
        
        $duplicateCategoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Duplicate Category',
            'is_active' => true,
        ]);
        
        $duplicateCategoryResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
        
        // Test brand duplicate name validation
        Brand::factory()->create(['name' => 'Duplicate Brand']);
        
        $duplicateBrandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Duplicate Brand',
            'is_active' => true,
        ]);
        
        $duplicateBrandResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function success_notification_after_creation()
    {
        // Create category and verify success message
        $categoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Success Category',
            'description' => 'Test success notification',
            'is_active' => true,
        ]);
        
        $categoryResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug'],
                'message',
            ]);
        
        // Verify the message is appropriate for user notification
        $this->assertStringContainsString('successfully', $categoryResponse->json('message'));
        
        // Create brand and verify success message
        $brandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Success Brand',
            'description' => 'Test success notification',
            'is_active' => true,
        ]);
        
        $brandResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Brand created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug'],
                'message',
            ]);
        
        // Verify the message is appropriate for user notification
        $this->assertStringContainsString('successfully', $brandResponse->json('message'));
    }

    /** @test */
    public function inline_creation_workflow_end_to_end()
    {
        // Step 1: Visit product create form
        $formResponse = $this->get(route('staff.products.create'));
        $formResponse->assertStatus(200);
        
        // Step 2: Create category inline
        $categoryResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'E2E Test Category',
            'description' => 'End-to-end test category',
            'is_active' => true,
        ]);
        
        $categoryResponse->assertStatus(200);
        $categoryId = $categoryResponse->json('data.id');
        
        // Step 3: Verify category appears in active list
        $categoriesResponse = $this->getJson(route('staff.categories.active'));
        $categoriesResponse->assertStatus(200);
        $categoryNames = collect($categoriesResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('E2E Test Category', $categoryNames);
        
        // Step 4: Create brand inline
        $brandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'E2E Test Brand',
            'description' => 'End-to-end test brand',
            'is_active' => true,
        ]);
        
        $brandResponse->assertStatus(200);
        $brandId = $brandResponse->json('data.id');
        
        // Step 5: Verify brand appears in active list
        $brandsResponse = $this->getJson(route('staff.brands.active'));
        $brandsResponse->assertStatus(200);
        $brandNames = collect($brandsResponse->json('data'))->pluck('name')->toArray();
        $this->assertContains('E2E Test Brand', $brandNames);
        
        // Step 6: Verify both items exist in database
        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'name' => 'E2E Test Category',
            'is_active' => true,
        ]);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brandId,
            'name' => 'E2E Test Brand',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function multiple_inline_creations_in_sequence()
    {
        // Create multiple categories in sequence
        $categories = ['Category One', 'Category Two', 'Category Three'];
        $createdIds = [];
        
        foreach ($categories as $name) {
            $response = $this->postJson(route('staff.categories.store-inline'), [
                'name' => $name,
                'is_active' => true,
            ]);
            
            $response->assertStatus(200);
            $createdIds[] = $response->json('data.id');
        }
        
        // Verify all categories exist
        foreach ($categories as $name) {
            $this->assertDatabaseHas('categories', ['name' => $name]);
        }
        
        // Verify all appear in active list
        $activeResponse = $this->getJson(route('staff.categories.active'));
        $activeNames = collect($activeResponse->json('data'))->pluck('name')->toArray();
        
        foreach ($categories as $name) {
            $this->assertContains($name, $activeNames);
        }
        
        // Create multiple brands in sequence
        $brands = ['Brand Alpha', 'Brand Beta', 'Brand Gamma'];
        
        foreach ($brands as $name) {
            $response = $this->postJson(route('staff.brands.store-inline'), [
                'name' => $name,
                'is_active' => true,
            ]);
            
            $response->assertStatus(200);
        }
        
        // Verify all brands exist
        foreach ($brands as $name) {
            $this->assertDatabaseHas('brands', ['name' => $name]);
        }
        
        // Verify all appear in active list
        $activeBrandsResponse = $this->getJson(route('staff.brands.active'));
        $activeBrandNames = collect($activeBrandsResponse->json('data'))->pluck('name')->toArray();
        
        foreach ($brands as $name) {
            $this->assertContains($name, $activeBrandNames);
        }
    }

    /** @test */
    public function inline_created_items_respect_is_active_flag()
    {
        // Create active category
        $activeResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Active Category',
            'is_active' => true,
        ]);
        
        $activeResponse->assertStatus(200);
        
        // Create inactive category
        $inactiveResponse = $this->postJson(route('staff.categories.store-inline'), [
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);
        
        $inactiveResponse->assertStatus(200);
        
        // Get active categories
        $activeListResponse = $this->getJson(route('staff.categories.active'));
        $activeNames = collect($activeListResponse->json('data'))->pluck('name')->toArray();
        
        // Verify only active category appears
        $this->assertContains('Active Category', $activeNames);
        $this->assertNotContains('Inactive Category', $activeNames);
        
        // Same test for brands
        $activeBrandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Active Brand',
            'is_active' => true,
        ]);
        
        $activeBrandResponse->assertStatus(200);
        
        $inactiveBrandResponse = $this->postJson(route('staff.brands.store-inline'), [
            'name' => 'Inactive Brand',
            'is_active' => false,
        ]);
        
        $inactiveBrandResponse->assertStatus(200);
        
        // Get active brands
        $activeBrandListResponse = $this->getJson(route('staff.brands.active'));
        $activeBrandNames = collect($activeBrandListResponse->json('data'))->pluck('name')->toArray();
        
        // Verify only active brand appears
        $this->assertContains('Active Brand', $activeBrandNames);
        $this->assertNotContains('Inactive Brand', $activeBrandNames);
    }
}
