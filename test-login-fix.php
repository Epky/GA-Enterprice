<?php

/**
 * Test script to verify Supabase RLS login fix
 * 
 * This script tests:
 * 1. Database connection
 * 2. Role setting capability
 * 3. User query with service_role
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Supabase RLS Login Fix Test ===\n\n";

try {
    // Test 1: Check database connection
    echo "1. Testing database connection...\n";
    $connection = DB::connection();
    echo "   ✓ Connected to: " . config('database.default') . "\n";
    echo "   ✓ Host: " . config('database.connections.pgsql.host') . "\n";
    echo "   ✓ Port: " . config('database.connections.pgsql.port') . "\n\n";
    
    // Test 2: Check current role
    echo "2. Checking current database role...\n";
    $currentRole = DB::selectOne("SELECT current_user, current_role");
    echo "   Current user: " . $currentRole->current_user . "\n";
    echo "   Current role: " . $currentRole->current_role . "\n\n";
    
    // Test 3: Try to set service_role
    echo "3. Attempting to set service_role...\n";
    try {
        DB::statement("SET ROLE service_role");
        echo "   ✓ Successfully set role to service_role\n\n";
    } catch (\Exception $e) {
        echo "   ✗ Failed to set service_role: " . $e->getMessage() . "\n";
        echo "   Note: This might be expected with connection pooler\n\n";
    }
    
    // Test 4: Check role after setting
    echo "4. Verifying current role after SET ROLE...\n";
    $newRole = DB::selectOne("SELECT current_user, current_role");
    echo "   Current user: " . $newRole->current_user . "\n";
    echo "   Current role: " . $newRole->current_role . "\n\n";
    
    // Test 5: Try to query users table
    echo "5. Testing users table query (the failing query from login)...\n";
    try {
        $testEmail = 'test@example.com';
        $user = DB::table('users')->where('email', $testEmail)->first();
        if ($user) {
            echo "   ✓ Successfully queried users table\n";
            echo "   Found user: " . $user->email . "\n\n";
        } else {
            echo "   ✓ Successfully queried users table (no user found with email: $testEmail)\n\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Failed to query users table: " . $e->getMessage() . "\n";
        echo "   This is the error we're trying to fix!\n\n";
    }
    
    // Test 6: Check RLS policies on users table
    echo "6. Checking RLS policies on users table...\n";
    try {
        $rlsStatus = DB::selectOne("
            SELECT relname, relrowsecurity 
            FROM pg_class 
            WHERE relname = 'users' AND relnamespace = 'public'::regnamespace
        ");
        if ($rlsStatus) {
            echo "   Table: " . $rlsStatus->relname . "\n";
            echo "   RLS Enabled: " . ($rlsStatus->relrowsecurity ? 'Yes' : 'No') . "\n\n";
        }
    } catch (\Exception $e) {
        echo "   Could not check RLS status: " . $e->getMessage() . "\n\n";
    }
    
    // Test 7: List available roles
    echo "7. Checking available roles...\n";
    try {
        $roles = DB::select("
            SELECT rolname 
            FROM pg_roles 
            WHERE rolname IN ('postgres', 'service_role', 'authenticated', 'anon')
            ORDER BY rolname
        ");
        echo "   Available roles:\n";
        foreach ($roles as $role) {
            echo "   - " . $role->rolname . "\n";
        }
        echo "\n";
    } catch (\Exception $e) {
        echo "   Could not list roles: " . $e->getMessage() . "\n\n";
    }
    
    echo "=== Test Complete ===\n";
    echo "\nRECOMMENDATIONS:\n";
    echo "- If service_role is available and can be set, the fix should work\n";
    echo "- If using connection pooler (port 6543), you may need to use direct connection (port 5432)\n";
    echo "- Ensure your database user has permission to SET ROLE to service_role\n";
    
} catch (\Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
