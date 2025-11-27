<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Fixing users table RLS policies...\n";

try {
    // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
    if (DB::getDriverName() !== 'pgsql') {
        echo "Not using PostgreSQL, skipping RLS fix.\n";
        exit(0);
    }

    echo "Setting role to service_role to bypass RLS...\n";
    
    // First, set the role to service_role to bypass RLS
    try {
        DB::statement("SET ROLE service_role");
        echo "✓ Role set to service_role\n";
    } catch (\Exception $e) {
        echo "⚠ Warning: Could not set role to service_role: " . $e->getMessage() . "\n";
        echo "Trying to continue anyway...\n";
    }

    echo "Dropping existing policies on users table...\n";
    
    // Drop existing policies on users table
    DB::statement("DROP POLICY IF EXISTS service_role_all_access ON users");
    DB::statement("DROP POLICY IF EXISTS users_auth_read ON users");
    DB::statement("DROP POLICY IF EXISTS users_self_read ON users");
    DB::statement("DROP POLICY IF EXISTS users_self_update ON users");

    echo "Creating new policies...\n";

    // Policy 1: Service role has full access (for backend operations)
    DB::statement("
        CREATE POLICY service_role_all_access ON users
        FOR ALL
        TO service_role
        USING (true)
        WITH CHECK (true)
    ");
    echo "✓ Created service_role_all_access policy\n";

    // Policy 2: Authenticated users can read their own data
    DB::statement("
        CREATE POLICY users_self_read ON users
        FOR SELECT
        TO authenticated
        USING (auth.uid() = id::text)
    ");
    echo "✓ Created users_self_read policy\n";

    // Policy 3: Authenticated users can update their own data
    DB::statement("
        CREATE POLICY users_self_update ON users
        FOR UPDATE
        TO authenticated
        USING (auth.uid() = id::text)
        WITH CHECK (auth.uid() = id::text)
    ");
    echo "✓ Created users_self_update policy\n";

    // Policy 4: Allow anon role to read users for authentication
    DB::statement("
        CREATE POLICY users_auth_read ON users
        FOR SELECT
        TO anon
        USING (true)
    ");
    echo "✓ Created users_auth_read policy\n";

    echo "\n✅ Successfully fixed users table RLS policies!\n";
    echo "\nNow you should be able to log in.\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
