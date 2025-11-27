<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Enable RLS on all tables and add comprehensive policies
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // All tables in the public schema
        $tables = [
            // Core tables
            'users', 'user_profiles', 'categories', 'brands', 'products',
            'product_images', 'product_variants', 'product_specifications',
            'inventory', 'inventory_movements',
            
            // Order and payment tables
            'orders', 'order_items', 'payments',
            
            // Cart tables
            'carts', 'cart_items',
            
            // Promotion tables
            'promotions', 'coupons', 'coupon_usage',
            
            // Audit tables
            'audit_logs', 'activity_logs',
        ];

        foreach ($tables as $table) {
            // Enable RLS on the table
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            
            // Drop existing service_role policy if it exists
            DB::statement("DROP POLICY IF EXISTS service_role_all_access ON {$table}");
            
            // Create service role policy (full access for backend)
            DB::statement("
                CREATE POLICY service_role_all_access ON {$table}
                FOR ALL
                USING (true)
                WITH CHECK (true)
            ");
        }

        // Add specific public read policies for tables that need public access
        $this->addPublicReadPolicies();
    }

    /**
     * Add public read policies for specific tables
     */
    private function addPublicReadPolicies(): void
    {
        // Categories - Public can read active categories
        DB::statement("DROP POLICY IF EXISTS categories_public_read ON categories");
        DB::statement("
            CREATE POLICY categories_public_read 
            ON categories 
            FOR SELECT 
            USING (is_active = true)
        ");

        // Brands - Public can read active brands
        DB::statement("DROP POLICY IF EXISTS brands_public_read ON brands");
        DB::statement("
            CREATE POLICY brands_public_read 
            ON brands 
            FOR SELECT 
            USING (is_active = true)
        ");

        // Products - Public can read active products
        DB::statement("DROP POLICY IF EXISTS products_public_read ON products");
        DB::statement("
            CREATE POLICY products_public_read 
            ON products 
            FOR SELECT 
            USING (status = 'active')
        ");

        // Product Images - Public can read all product images
        DB::statement("DROP POLICY IF EXISTS product_images_public_read ON product_images");
        DB::statement("
            CREATE POLICY product_images_public_read 
            ON product_images 
            FOR SELECT 
            USING (true)
        ");

        // Product Specifications - Public can read all specifications
        DB::statement("DROP POLICY IF EXISTS product_specifications_public_read ON product_specifications");
        DB::statement("
            CREATE POLICY product_specifications_public_read 
            ON product_specifications 
            FOR SELECT 
            USING (true)
        ");

        // Product Variants - Public can read active variants
        DB::statement("DROP POLICY IF EXISTS product_variants_public_read ON product_variants");
        DB::statement("
            CREATE POLICY product_variants_public_read 
            ON product_variants 
            FOR SELECT 
            USING (is_active = true)
        ");

        // Inventory - Public can read inventory (for stock display)
        DB::statement("DROP POLICY IF EXISTS inventory_public_read ON inventory");
        DB::statement("
            CREATE POLICY inventory_public_read 
            ON inventory 
            FOR SELECT 
            USING (true)
        ");

        // Promotions - Public can read active promotions
        DB::statement("DROP POLICY IF EXISTS promotions_public_read ON promotions");
        DB::statement("
            CREATE POLICY promotions_public_read 
            ON promotions 
            FOR SELECT 
            USING (
                is_active = true 
                AND start_date <= CURRENT_TIMESTAMP 
                AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip RLS for SQLite
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tables = [
            'users', 'user_profiles', 'categories', 'brands', 'products',
            'product_images', 'product_variants', 'product_specifications',
            'inventory', 'inventory_movements',
            'orders', 'order_items', 'payments',
            'carts', 'cart_items',
            'promotions', 'coupons', 'coupon_usage',
            'audit_logs', 'activity_logs',
        ];

        foreach ($tables as $table) {
            // Drop all policies
            DB::statement("DROP POLICY IF EXISTS service_role_all_access ON {$table}");
            DB::statement("DROP POLICY IF EXISTS {$table}_public_read ON {$table}");
            
            // Disable RLS
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
