<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes to optimize common queries.
     */
    public function up(): void
    {
        // Use raw SQL with IF NOT EXISTS for PostgreSQL
        $indexes = [
            // Products table indexes
            "CREATE INDEX IF NOT EXISTS idx_products_status ON products(status)",
            "CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured)",
            "CREATE INDEX IF NOT EXISTS idx_products_new_arrival ON products(is_new_arrival)",
            "CREATE INDEX IF NOT EXISTS idx_products_best_seller ON products(is_best_seller)",
            "CREATE INDEX IF NOT EXISTS idx_products_category_status ON products(category_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_products_brand_status ON products(brand_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_products_slug ON products(slug)",
            "CREATE INDEX IF NOT EXISTS idx_products_sku ON products(sku)",

            
            // Categories table indexes
            "CREATE INDEX IF NOT EXISTS idx_categories_active ON categories(is_active)",
            "CREATE INDEX IF NOT EXISTS idx_categories_parent_order ON categories(parent_id, display_order)",
            "CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug)",
            
            // Brands table indexes
            "CREATE INDEX IF NOT EXISTS idx_brands_active ON brands(is_active)",
            "CREATE INDEX IF NOT EXISTS idx_brands_slug ON brands(slug)",
            
            // Product images table indexes
            "CREATE INDEX IF NOT EXISTS idx_product_images_product_order ON product_images(product_id, display_order)",
            "CREATE INDEX IF NOT EXISTS idx_product_images_primary ON product_images(product_id, is_primary)",
            
            // Inventory table indexes
            "CREATE INDEX IF NOT EXISTS idx_inventory_product ON inventory(product_id)",
            "CREATE INDEX IF NOT EXISTS idx_inventory_low_stock ON inventory(product_id, quantity_available, reorder_level)",
            
            // Product variants table indexes
            "CREATE INDEX IF NOT EXISTS idx_variants_product_active ON product_variants(product_id, is_active)",
            "CREATE INDEX IF NOT EXISTS idx_variants_sku ON product_variants(sku)",
        ];
        
        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'idx_products_status',
            'idx_products_featured',
            'idx_products_new_arrival',
            'idx_products_best_seller',
            'idx_products_category_status',
            'idx_products_brand_status',
            'idx_products_slug',
            'idx_products_sku',
            'idx_categories_active',
            'idx_categories_parent_order',
            'idx_categories_slug',
            'idx_brands_active',
            'idx_brands_slug',
            'idx_product_images_product_order',
            'idx_product_images_primary',
            'idx_inventory_product',
            'idx_inventory_low_stock',
            'idx_variants_product_active',
            'idx_variants_sku',
        ];
        
        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
