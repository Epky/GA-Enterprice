<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Fix users table RLS to allow authentication
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Drop existing policies on users table
        DB::statement("DROP POLICY IF EXISTS service_role_all_access ON users");
        DB::statement("DROP POLICY IF EXISTS users_auth_read ON users");
        DB::statement("DROP POLICY IF EXISTS users_self_read ON users");
        DB::statement("DROP POLICY IF EXISTS users_self_update ON users");

        // Policy 1: Service role has full access (for backend operations)
        DB::statement("
            CREATE POLICY service_role_all_access ON users
            FOR ALL
            TO service_role
            USING (true)
            WITH CHECK (true)
        ");

        // Policy 2: Authenticated users can read their own data
        DB::statement("
            CREATE POLICY users_self_read ON users
            FOR SELECT
            TO authenticated
            USING (auth.uid() = id::text)
        ");

        // Policy 3: Authenticated users can update their own data
        DB::statement("
            CREATE POLICY users_self_update ON users
            FOR UPDATE
            TO authenticated
            USING (auth.uid() = id::text)
            WITH CHECK (auth.uid() = id::text)
        ");

        // Policy 4: Allow anon role to read users for authentication
        // This is critical for login - allows querying by email
        DB::statement("
            CREATE POLICY users_auth_read ON users
            FOR SELECT
            TO anon
            USING (true)
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

        // Drop all policies
        DB::statement("DROP POLICY IF EXISTS service_role_all_access ON users");
        DB::statement("DROP POLICY IF EXISTS users_auth_read ON users");
        DB::statement("DROP POLICY IF EXISTS users_self_read ON users");
        DB::statement("DROP POLICY IF EXISTS users_self_update ON users");

        // Recreate the original service_role policy
        DB::statement("
            CREATE POLICY service_role_all_access ON users
            FOR ALL
            USING (true)
            WITH CHECK (true)
        ");
    }
};
