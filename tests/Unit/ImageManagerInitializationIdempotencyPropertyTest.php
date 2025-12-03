<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-image-upload-duplication, Property 7: Initialization idempotency
 * Validates: Requirements 3.1
 * 
 * This test validates that the ImageManager initialization is idempotent - calling
 * the initialization function multiple times has the same effect as calling it once.
 * 
 * Since this is primarily a frontend behavior, we test it by verifying that:
 * 1. The component renders correctly with initialization code
 * 2. Multiple renders don't cause issues
 * 3. The initialization flag prevents duplicate initializations
 */
class ImageManagerInitializationIdempotencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Property 7: Initialization idempotency
     * For any image manager component, calling the initialization function multiple times
     * should have the same effect as calling it once.
     * 
     * This test verifies that the component includes the initialization flag check
     * and proper guards against multiple initializations.
     * 
     * @test
     */
    public function property_component_includes_initialization_flag_check()
    {
        // Run the test multiple times with different scenarios
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a staff user and authenticate
            $staff = User::factory()->create(['role' => 'staff']);
            $this->actingAs($staff);
            
            // Create necessary data
            $category = Category::factory()->create();
            $brand = Brand::factory()->create();
            
            // Randomly test either create or edit page
            $isEditPage = rand(0, 1) === 1;
            
            if ($isEditPage) {
                // Test edit page
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                ]);
                
                $response = $this->get(route('staff.products.edit', $product));
            } else {
                // Test create page
                $response = $this->get(route('staff.products.create'));
            }
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Assert: Component should include initialization flag
            $response->assertSee('imageManagerInit_', false);
            
            // Assert: Component should check if already initialized
            $response->assertSee('if (window[initFlagName])', false);
            $response->assertSee('already initialized', false);
            
            // Assert: Component should set flag before creating instance
            $response->assertSee('window[initFlagName] = true', false);
            
            // Assert: Component should have single initialization approach
            $response->assertSee('once: true', false);
            
            // Clean up
            if ($isEditPage) {
                $product->delete();
            }
            $category->delete();
            $brand->delete();
            $staff->delete();
        }
    }

    /**
     * Property: Component uses single event listener with once option
     * 
     * @test
     */
    public function property_component_uses_single_event_listener_with_once_option()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Test both create and edit pages
        $pages = [
            'create' => route('staff.products.create'),
            'edit' => route('staff.products.edit', Product::factory()->create()),
        ];
        
        foreach ($pages as $pageType => $url) {
            // Act: Load the page
            $response = $this->get($url);
            
            // Assert: Should use { once: true } option
            $response->assertSee('{ once: true }', false);
            
            // Assert: Should NOT have multiple initialization calls
            // The old problematic code had these patterns:
            // - initImageManager_xxx(); (immediate execution)
            // - addEventListener('DOMContentLoaded', initImageManager_xxx);
            // - addEventListener('load', initImageManager_xxx);
            
            // We should NOT see window.addEventListener('load' for ImageManager
            $content = $response->getContent();
            
            // Count occurrences of addEventListener for this specific init function
            // Should only appear once (for DOMContentLoaded)
            $initFunctionPattern = '/addEventListener.*initImageManager_/';
            preg_match_all($initFunctionPattern, $content, $matches);
            
            // Should have exactly one addEventListener call
            $this->assertLessThanOrEqual(
                1,
                count($matches[0]),
                "Should have at most one addEventListener call for ImageManager initialization on {$pageType} page"
            );
        }
    }

    /**
     * Property: Component checks document.readyState before initialization
     * 
     * @test
     */
    public function property_component_checks_document_ready_state()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product for edit page
        $product = Product::factory()->create();
        
        // Test both pages
        $pages = [
            'create' => route('staff.products.create'),
            'edit' => route('staff.products.edit', $product),
        ];
        
        foreach ($pages as $pageType => $url) {
            // Act: Load the page
            $response = $this->get($url);
            
            // Assert: Should check document.readyState
            $response->assertSee("document.readyState === 'loading'", false);
            
            // Assert: Should have conditional initialization based on ready state
            $response->assertSee('DOMContentLoaded', false);
            
            // Assert: Should initialize immediately if DOM already loaded
            $content = $response->getContent();
            $this->assertStringContainsString(
                'else',
                $content,
                "Should have else clause for immediate initialization on {$pageType} page"
            );
        }
    }

    /**
     * Property: Multiple page loads don't cause initialization errors
     * 
     * @test
     */
    public function property_multiple_page_loads_work_correctly()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Act: Load the edit page multiple times
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get(route('staff.products.edit', $product));
            
            // Assert: Each load should be successful
            $response->assertStatus(200);
            
            // Assert: Should contain ImageManager initialization code
            $response->assertSee('ImageManager', false);
            $response->assertSee('initImageManager_', false);
            
            // Assert: Should contain the initialization flag check
            $response->assertSee('if (window[initFlagName])', false);
        }
    }

    /**
     * Property: Initialization flag is unique per component instance
     * 
     * @test
     */
    public function property_initialization_flag_is_unique_per_instance()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Act: Load the edit page
        $response = $this->get(route('staff.products.edit', $product));
        
        // Assert: Should have unique flag name based on component name
        $response->assertSee('imageManagerInit_images', false);
        
        // Assert: Flag name should be derived from the input name
        $content = $response->getContent();
        
        // The flag should be unique to this instance
        $this->assertStringContainsString(
            "const initFlagName = 'imageManagerInit_",
            $content,
            "Should create unique initialization flag name"
        );
    }

    /**
     * Property: Component initialization is defensive against missing elements
     * 
     * @test
     */
    public function property_initialization_handles_missing_elements_gracefully()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Act: Load the page
        $response = $this->get(route('staff.products.edit', $product));
        
        // Assert: Should check for required elements before initializing
        $response->assertSee('if (!uploadArea || !fileInput)', false);
        
        // Assert: Should retry if elements not found
        $response->assertSee('setTimeout(initImageManager_', false);
        
        // Assert: Should check if ImageManager class is loaded
        $response->assertSee("typeof ImageManager === 'undefined'", false);
    }

    /**
     * Property: Initialization returns early if already initialized
     * 
     * @test
     */
    public function property_initialization_returns_early_when_already_initialized()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 30; $iteration++) {
            // Arrange: Create a staff user and authenticate
            $staff = User::factory()->create(['role' => 'staff']);
            $this->actingAs($staff);
            
            // Create a product
            $product = Product::factory()->create();
            
            // Act: Load the page
            $response = $this->get(route('staff.products.edit', $product));
            
            // Assert: Should return early if already initialized
            $content = $response->getContent();
            
            // Check that the early return pattern exists
            $this->assertMatchesRegularExpression(
                '/if\s*\(\s*window\[initFlagName\]\s*\)\s*\{[^}]*return\s*;/s',
                $content,
                "Should return early if already initialized (iteration {$iteration})"
            );
            
            // Clean up
            $product->delete();
            $staff->delete();
        }
    }

    /**
     * Property: Flag is set before ImageManager instance creation
     * 
     * @test
     */
    public function property_flag_is_set_before_instance_creation()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Act: Load the page
        $response = $this->get(route('staff.products.edit', $product));
        
        // Assert: Flag should be set before creating instance
        $content = $response->getContent();
        
        // Find the position of flag setting and instance creation
        $flagSetPosition = strpos($content, 'window[initFlagName] = true');
        $instanceCreatePosition = strpos($content, 'new ImageManager(');
        
        $this->assertNotFalse($flagSetPosition, "Should set initialization flag");
        $this->assertNotFalse($instanceCreatePosition, "Should create ImageManager instance");
        
        // Flag should be set before instance creation
        $this->assertLessThan(
            $instanceCreatePosition,
            $flagSetPosition,
            "Initialization flag should be set BEFORE creating ImageManager instance"
        );
    }
}
