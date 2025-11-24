<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create beauty brands
        $brands = $this->createBrands();
        
        // Create beauty categories
        $categories = $this->createCategories();
        
        // Create products with relationships
        $this->createProducts($brands, $categories);
    }

    /**
     * Create beauty brands.
     */
    private function createBrands(): array
    {
        $beautyBrands = [
            ['name' => 'L\'Oréal Paris', 'description' => 'Because you\'re worth it'],
            ['name' => 'Maybelline New York', 'description' => 'Make it happen'],
            ['name' => 'Revlon', 'description' => 'Live boldly'],
            ['name' => 'MAC Cosmetics', 'description' => 'All ages, all races, all genders'],
            ['name' => 'Clinique', 'description' => 'Allergy tested. 100% fragrance free'],
            ['name' => 'Estée Lauder', 'description' => 'Every woman can be beautiful'],
            ['name' => 'Lancôme', 'description' => 'French luxury beauty'],
            ['name' => 'Neutrogena', 'description' => 'Dermatologist recommended'],
            ['name' => 'Olay', 'description' => 'Your best beautiful'],
            ['name' => 'The Body Shop', 'description' => 'Enrich not exploit'],
        ];

        $brands = [];
        foreach ($beautyBrands as $brandData) {
            $brands[] = Brand::firstOrCreate(
                ['name' => $brandData['name']],
                [
                    'slug' => Str::slug($brandData['name']),
                    'description' => $brandData['description'],
                    'is_active' => true,
                ]
            );
        }

        return $brands;
    }

    /**
     * Create beauty categories with hierarchy.
     */
    private function createCategories(): array
    {
        $categories = [];

        // Root categories
        $rootCategories = [
            'Skincare' => 'Complete skincare solutions for all skin types',
            'Makeup' => 'Beauty products to enhance your natural features',
            'Haircare' => 'Professional hair care products',
            'Fragrance' => 'Luxury perfumes and body sprays',
            'Body Care' => 'Body care essentials for daily use',
        ];

        foreach ($rootCategories as $name => $description) {
            $categories[$name] = Category::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => $description,
                    'is_active' => true,
                    'display_order' => count($categories),
                ]
            );
        }

        // Skincare subcategories
        $skincareSubcategories = [
            'Face Cleansers', 'Moisturizers', 'Serums', 'Sunscreen',
            'Toners', 'Exfoliators', 'Face Masks', 'Eye Care'
        ];

        foreach ($skincareSubcategories as $index => $name) {
            Category::firstOrCreate(
                ['name' => $name, 'parent_id' => $categories['Skincare']->id],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                    'display_order' => $index,
                ]
            );
        }

        // Makeup subcategories
        $makeupSubcategories = [
            'Foundation', 'Concealer', 'Lipstick', 'Lip Gloss',
            'Eyeshadow', 'Mascara', 'Eyeliner', 'Blush', 'Bronzer'
        ];

        foreach ($makeupSubcategories as $index => $name) {
            Category::firstOrCreate(
                ['name' => $name, 'parent_id' => $categories['Makeup']->id],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                    'display_order' => $index,
                ]
            );
        }

        return $categories;
    }

    /**
     * Create products with full relationships.
     */
    private function createProducts(array $brands, array $categories): void
    {
        // Get all categories (including subcategories)
        $allCategories = Category::all();
        
        // Create 50 products
        for ($i = 0; $i < 50; $i++) {
            $product = Product::factory()
                ->for($brands[array_rand($brands)])
                ->for($allCategories->random())
                ->create();

            // Add 2-4 images per product
            $imageCount = rand(2, 4);
            for ($j = 0; $j < $imageCount; $j++) {
                ProductImage::factory()
                    ->for($product)
                    ->create([
                        'is_primary' => $j === 0, // First image is primary
                        'display_order' => $j,
                    ]);
            }

            // Add 3-6 specifications per product
            $specCount = rand(3, 6);
            $specKeys = [
                'skin_type', 'coverage', 'finish', 'spf', 'cruelty_free',
                'paraben_free', 'ingredients', 'volume', 'application_method'
            ];
            
            $selectedSpecs = array_rand(array_flip($specKeys), $specCount);
            foreach ($selectedSpecs as $index => $specKey) {
                ProductSpecification::factory()
                    ->for($product)
                    ->create([
                        'spec_key' => $specKey,
                        'display_order' => $index,
                    ]);
            }

            // Add variants for some products (30% chance)
            if (rand(1, 100) <= 30) {
                $variantCount = rand(2, 5);
                $variantType = ['color', 'shade', 'size'][array_rand(['color', 'shade', 'size'])];
                
                for ($v = 0; $v < $variantCount; $v++) {
                    $variant = ProductVariant::factory()
                        ->for($product)
                        ->create([
                            'variant_type' => $variantType,
                        ]);

                    // Create inventory for each variant
                    Inventory::factory()
                        ->for($product)
                        ->for($variant, 'variant')
                        ->create();
                }
            } else {
                // Create inventory for product without variants
                Inventory::factory()
                    ->for($product)
                    ->create();
            }
        }

        // Create some featured products
        Product::inRandomOrder()->limit(10)->update(['is_featured' => true]);
        
        // Create some new arrivals
        Product::inRandomOrder()->limit(8)->update(['is_new_arrival' => true]);
        
        // Create some best sellers
        Product::inRandomOrder()->limit(6)->update(['is_best_seller' => true]);

        // Create some products on sale
        Product::inRandomOrder()->limit(15)->each(function ($product) {
            $product->update([
                'sale_price' => $product->base_price * 0.8, // 20% off
            ]);
        });

        // Create some low stock situations
        Inventory::inRandomOrder()->limit(10)->each(function ($inventory) {
            $inventory->update([
                'quantity_available' => rand(0, $inventory->reorder_level),
            ]);
        });
    }
}