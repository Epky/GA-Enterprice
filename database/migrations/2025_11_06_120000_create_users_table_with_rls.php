<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Check if users table already exists
            if (Schema::hasTable('users')) {
                Log::info('Users table already exists, ensuring proper structure...');
                $this->ensureProperStructure();
            } else {
                Log::info('Creating users table...');
                $this->createUsersTable();
            }

            // Add performance indexes
            $this->addPerformanceIndexes();

            // Setup RLS policies for Supabase
            $this->setupRLSPolicies();

            Log::info('Users table migration completed successfully');
        } catch (\Exception $e) {
            Log::error('Users table migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create the users table with all required fields
     */
    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'staff', 'customer'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Log::info('Users table created with all required fields');
    }

    /**
     * Ensure existing users table has proper structure
     */
    private function ensureProperStructure(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add name column if it doesn't exist
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
                Log::info('Added name column to users table');
            }

            // Add email_verified_at column if it doesn't exist
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
                Log::info('Added email_verified_at column to users table');
            }

            // Add role column if it doesn't exist
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'staff', 'customer'])->default('customer')->after('password');
                Log::info('Added role column to users table');
            }

            // Add is_active column if it doesn't exist
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
                Log::info('Added is_active column to users table');
            }

            // Add last_login_at column if it doesn't exist
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
                Log::info('Added last_login_at column to users table');
            }

            // Add remember_token column if it doesn't exist
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
                Log::info('Added remember_token column to users table');
            }
        });
    }

    /**
     * Add performance indexes for frequently queried columns
     */
    private function addPerformanceIndexes(): void
    {
        try {
            // Check if we're using PostgreSQL (required for PostgreSQL-specific index queries)
            if (DB::getDriverName() !== 'pgsql') {
                Log::info('Skipping PostgreSQL-specific indexes: PostgreSQL connection required');
                return;
            }

            // Check and add email index if it doesn't exist
            $emailIndexExists = DB::select("
                SELECT indexname FROM pg_indexes 
                WHERE tablename = 'users' AND indexname LIKE '%email%'
            ");

            if (empty($emailIndexExists)) {
                DB::statement('CREATE INDEX CONCURRENTLY idx_users_email ON users(email)');
                Log::info('Added email index to users table');
            }

            // Check and add role index if it doesn't exist
            $roleIndexExists = DB::select("
                SELECT indexname FROM pg_indexes 
                WHERE tablename = 'users' AND indexname LIKE '%role%'
            ");

            if (empty($roleIndexExists)) {
                DB::statement('CREATE INDEX CONCURRENTLY idx_users_role ON users(role)');
                Log::info('Added role index to users table');
            }

            // Check and add is_active index if it doesn't exist
            $activeIndexExists = DB::select("
                SELECT indexname FROM pg_indexes 
                WHERE tablename = 'users' AND indexname LIKE '%is_active%'
            ");

            if (empty($activeIndexExists)) {
                DB::statement('CREATE INDEX CONCURRENTLY idx_users_is_active ON users(is_active)');
                Log::info('Added is_active index to users table');
            }

        } catch (\Exception $e) {
            Log::warning('Failed to add some indexes (they may already exist)', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Setup Row Level Security policies for Supabase
     */
    private function setupRLSPolicies(): void
    {
        try {
            // Check if we're using PostgreSQL (required for RLS)
            if (DB::getDriverName() !== 'pgsql') {
                Log::info('Skipping RLS setup: PostgreSQL connection required');
                return;
            }

            // Enable RLS on users table
            DB::statement('ALTER TABLE users ENABLE ROW LEVEL SECURITY');
            Log::info('Enabled RLS on users table');

            // Create service role full access policy
            DB::statement("
                DROP POLICY IF EXISTS service_role_users_all ON users
            ");
            
            DB::statement("
                CREATE POLICY service_role_users_all ON users
                FOR ALL TO service_role
                USING (true)
                WITH CHECK (true)
            ");
            Log::info('Created service role full access policy for users table');

            // Create policy for users to access their own records
            DB::statement("
                DROP POLICY IF EXISTS users_own_records ON users
            ");
            
            DB::statement("
                CREATE POLICY users_own_records ON users
                FOR ALL TO authenticated
                USING (auth.uid()::text = id::text)
                WITH CHECK (auth.uid()::text = id::text)
            ");
            Log::info('Created user self-access policy for users table');

            // Create policy for admin users to manage all records
            DB::statement("
                DROP POLICY IF EXISTS admin_users_all ON users
            ");
            
            DB::statement("
                CREATE POLICY admin_users_all ON users
                FOR ALL TO authenticated
                USING (
                    EXISTS (
                        SELECT 1 FROM users u 
                        WHERE u.id::text = auth.uid()::text 
                        AND u.role = 'admin'
                        AND u.is_active = true
                    )
                )
                WITH CHECK (
                    EXISTS (
                        SELECT 1 FROM users u 
                        WHERE u.id::text = auth.uid()::text 
                        AND u.role = 'admin'
                        AND u.is_active = true
                    )
                )
            ");
            Log::info('Created admin management policy for users table');

        } catch (\Exception $e) {
            Log::error('Failed to setup RLS policies for users table', [
                'error' => $e->getMessage()
            ]);
            
            // Don't fail the migration if RLS setup fails
            // This allows the migration to work in non-Supabase environments
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Check if we're using PostgreSQL (required for RLS)
            if (DB::getDriverName() === 'pgsql') {
                // Drop RLS policies first
                DB::statement('DROP POLICY IF EXISTS admin_users_all ON users');
                DB::statement('DROP POLICY IF EXISTS users_own_records ON users');
                DB::statement('DROP POLICY IF EXISTS service_role_users_all ON users');
                
                // Disable RLS
                DB::statement('ALTER TABLE users DISABLE ROW LEVEL SECURITY');
                
                Log::info('Dropped RLS policies for users table');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to drop RLS policies during rollback', [
                'error' => $e->getMessage()
            ]);
        }

        // Drop the users table
        Schema::dropIfExists('users');
        
        Log::info('Users table migration rolled back');
    }
};