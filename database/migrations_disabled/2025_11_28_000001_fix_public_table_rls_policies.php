<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create policies for public read access to categories and products
        // This allows the home page and shop to work without authentication
        
        // Categories - Allow public read access
        DB::statement("
            CREATE POLICY IF NOT EXISTS categories_public_read 
            ON categories 
            FOR SELECT 
            USING (is_active = true)
        ");
        
        // Products - Allow public read access to active products
        DB::statement("
            CREATE POLICY IF NOT EXISTS products_public_read 
            ON products 
            FOR SELECT 
            USING (status = 'active')
        ");
        
        // Product Images - Allow public read access
        DB::statement("
            CREATE POLICY IF NOT EXISTS product_images_public_read 
            ON product_images 
            FOR SELECT 
            USING (true)
        ");
        
        // Product Specifications - Allow public read access
        DB::statement("
            CREATE POLICY IF NOT EXISTS product_specifications_public_read 
            ON product_specifications 
            FOR SELECT 
            USING (true)
        ");
        
        // Product Variants - Allow public read access to active variants
        DB::statement("
            CREATE POLICY IF NOT EXISTS product_variants_public_read 
            ON product_variants 
            FOR SELECT 
            USING (is_active = true)
        ");
        
        // Brands - Allow public read access to active brands
        DB::statement("
            CREATE POLICY IF NOT EXISTS brands_public_read 
            ON brands 
            FOR SELECT 
            USING (is_active = true)
        ");
        
        // Inventory - Allow public read access (for stock display)
        DB::statement("
            CREATE POLICY IF NOT EXISTS inventory_public_read 
            ON inventory 
            FOR SELECT 
            USING (true)
        ");
        
        // Promotions - Allow public read access to active promotions
        DB::statement("
            CREATE POLICY IF NOT EXISTS promotions_public_read 
            ON promotions 
            FOR SELECT 
            USING (is_active = true AND start_date <= CURRENT_TIMESTAMP AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the public read policies
        DB::statement("DROP POLICY IF EXISTS categories_public_read ON categories");
        DB::statement("DROP POLICY IF EXISTS products_public_read ON products");
        DB::statement("DROP POLICY IF EXISTS product_images_public_read ON product_images");
        DB::statement("DROP POLICY IF EXISTS product_specifications_public_read ON product_specifications");
        DB::statement("DROP POLICY IF EXISTS product_variants_public_read ON product_variants");
        DB::statement("DROP POLICY IF EXISTS brands_public_read ON brands");
        DB::statement("DROP POLICY IF EXISTS inventory_public_read ON inventory");
        DB::statement("DROP POLICY IF EXISTS promotions_public_read ON promotions");
    }
};
