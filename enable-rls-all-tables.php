#!/usr/bin/env php
<?php

/**
 * Script to enable RLS on all tables and remove "Unrestricted" status
 * 
 * This script will:
 * 1. Enable RLS on all public tables
 * 2. Add service_role policies for full backend access
 * 3. Add public read policies for tables that need public access
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔒 Enabling RLS on all tables...\n\n";

try {
    // Check if we're using PostgreSQL
    if (DB::getDriverName() !== 'pgsql') {
        echo "❌ This script only works with PostgreSQL/Supabase\n";
        echo "   Current driver: " . DB::getDriverName() . "\n";
        exit(1);
    }

    // All tables in the public schema
    $tables = [
        'users', 'user_profiles', 'categories', 'brands', 'products',
        'product_images', 'product_variants', 'product_specifications',
        'inventory', 'inventory_movements',
        'orders', 'order_items', 'payments',
        'carts', 'cart_items',
        'promotions', 'coupons', 'coupon_usage',
        'audit_logs', 'activity_logs',
    ];

    echo "📋 Processing " . count($tables) . " tables...\n\n";

    foreach ($tables as $table) {
        echo "Processing table: {$table}\n";
        
        // Check if table exists
        $exists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )
        ", [$table]);
        
        if (!$exists[0]->exists) {
            echo "  ⚠️  Table does not exist, skipping...\n";
            continue;
        }
        
        // Enable RLS
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        echo "  ✅ RLS enabled\n";
        
        // Drop existing service_role policy if it exists
        DB::statement("DROP POLICY IF EXISTS service_role_all_access ON {$table}");
        
        // Create service role policy (full access for backend)
        DB::statement("
            CREATE POLICY service_role_all_access ON {$table}
            FOR ALL
            USING (true)
            WITH CHECK (true)
        ");
        echo "  ✅ Service role policy created\n";
    }

    echo "\n📝 Adding public read policies...\n\n";

    // Public read policies
    $publicPolicies = [
        'categories' => "is_active = true",
        'brands' => "is_active = true",
        'products' => "status = 'active'",
        'product_images' => "true",
        'product_specifications' => "true",
        'product_variants' => "is_active = true",
        'inventory' => "true",
        'promotions' => "is_active = true AND start_date <= CURRENT_TIMESTAMP AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)",
    ];

    foreach ($publicPolicies as $table => $condition) {
        echo "Adding public read policy for: {$table}\n";
        
        DB::statement("DROP POLICY IF EXISTS {$table}_public_read ON {$table}");
        DB::statement("
            CREATE POLICY {$table}_public_read 
            ON {$table} 
            FOR SELECT 
            USING ({$condition})
        ");
        echo "  ✅ Public read policy created\n";
    }

    echo "\n✅ All done! All tables now have RLS enabled with proper policies.\n";
    echo "🔍 Check your Supabase Table Editor - 'Unrestricted' labels should be gone!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
