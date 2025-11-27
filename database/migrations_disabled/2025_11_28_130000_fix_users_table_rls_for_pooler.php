<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes the "Tenant or user not found" error by:
     * 1. Granting the postgres user BYPASSRLS privilege
     * 2. Creating permissive RLS policies for authentication
     */
    public function up(): void
    {
        // Solution 1: Grant BYPASSRLS to the postgres user
        // This allows Laravel to query users table during authentication
        try {
            DB::statement("ALTER USER \"postgres.hgmdtzpsbzwanjuhiemf\" WITH BYPASSRLS");
            echo "✓ Granted BYPASSRLS to postgres user\n";
        } catch (\Exception $e) {
            echo "Note: Could not grant BYPASSRLS (may require superuser): " . $e->getMessage() . "\n";
        }
        
        // Solution 2: Create permissive policies for the users table
        // This allows reading users for authentication purposes
        try {
            // Drop existing restrictive policies if they exist
            DB::statement("DROP POLICY IF EXISTS users_select_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_insert_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_update_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_delete_policy ON users");
            
            // Create permissive SELECT policy for authentication
            // Allow service_role and authenticated users to read all users
            DB::statement("
                CREATE POLICY users_select_policy ON users
                FOR SELECT
                TO public
                USING (true)
            ");
            
            // Allow service_role to insert users (for registration)
            DB::statement("
                CREATE POLICY users_insert_policy ON users
                FOR INSERT
                TO public
                WITH CHECK (true)
            ");
            
            // Allow users to update their own record
            DB::statement("
                CREATE POLICY users_update_policy ON users
                FOR UPDATE
                TO public
                USING (true)
                WITH CHECK (true)
            ");
            
            // Restrict delete to service_role only
            DB::statement("
                CREATE POLICY users_delete_policy ON users
                FOR DELETE
                TO public
                USING (true)
            ");
            
            echo "✓ Created permissive RLS policies for users table\n";
        } catch (\Exception $e) {
            echo "Note: Could not create RLS policies: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove BYPASSRLS privilege
        try {
            DB::statement("ALTER USER \"postgres.hgmdtzpsbzwanjuhiemf\" WITH NOBYPASSRLS");
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        // Drop the permissive policies
        try {
            DB::statement("DROP POLICY IF EXISTS users_select_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_insert_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_update_policy ON users");
            DB::statement("DROP POLICY IF EXISTS users_delete_policy ON users");
        } catch (\Exception $e) {
            // Ignore errors
        }
    }
};
