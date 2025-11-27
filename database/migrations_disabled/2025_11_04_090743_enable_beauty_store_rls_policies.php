<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Part 4: Row Level Security
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Enable RLS on all tables
        $tables = [
            'users', 'user_profiles', 'categories', 'brands', 'products',
            'product_images', 'product_variants', 'product_specifications',
            'inventory', 'inventory_movements', 'customer_addresses',
            'orders', 'order_items', 'payments', 'cart_items', 'wishlist_items',
            'product_reviews', 'review_images', 'promotions', 'coupons',
            'coupon_usage', 'audit_logs', 'activity_logs'
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        }

        // Service role has full access to all tables
        foreach ($tables as $table) {
            DB::statement("
                CREATE POLICY service_role_all_access ON {$table}
                FOR ALL TO service_role
                USING (true)
                WITH CHECK (true)
            ");
        }

        // Public can read active products and categories
        DB::statement("
            CREATE POLICY public_read_products ON products
            FOR SELECT TO anon
            USING (status = 'active')
        ");

        DB::statement("
            CREATE POLICY public_read_categories ON categories
            FOR SELECT TO anon
            USING (is_active = true)
        ");

        DB::statement("
            CREATE POLICY public_read_brands ON brands
            FOR SELECT TO anon
            USING (is_active = true)
        ");

        DB::statement("
            CREATE POLICY public_read_product_images ON product_images
            FOR SELECT TO anon
            USING (true)
        ");

        DB::statement("
            CREATE POLICY public_read_product_variants ON product_variants
            FOR SELECT TO anon
            USING (is_active = true)
        ");

        DB::statement("
            CREATE POLICY public_read_approved_reviews ON product_reviews
            FOR SELECT TO anon
            USING (is_approved = true)
        ");

        // Note: For authenticated user policies, we'll handle this at the application level
        // since we're using Laravel's authentication system, not Supabase Auth
        // The service_role policy above gives full access to the Laravel backend
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Drop all policies
        $tables = [
            'users', 'user_profiles', 'categories', 'brands', 'products',
            'product_images', 'product_variants', 'product_specifications',
            'inventory', 'inventory_movements', 'customer_addresses',
            'orders', 'order_items', 'payments', 'cart_items', 'wishlist_items',
            'product_reviews', 'review_images', 'promotions', 'coupons',
            'coupon_usage', 'audit_logs', 'activity_logs'
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS service_role_all_access ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};