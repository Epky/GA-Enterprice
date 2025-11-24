<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Create missing tables and fix RLS
     */
    public function up(): void
    {
        Log::info('=== Creating Missing System Tables and Fixing RLS ===');

        // Skip RLS operations for SQLite
        $isPostgres = DB::getDriverName() === 'pgsql';

        // Create job_batches table if it doesn't exist
        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->text('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
            
            if ($isPostgres) {
                DB::statement("ALTER TABLE job_batches DISABLE ROW LEVEL SECURITY");
                Log::info('✓ Created job_batches table with RLS disabled');
            } else {
                Log::info('✓ Created job_batches table');
            }
        }

        // Create failed_jobs table if it doesn't exist
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
            
            if ($isPostgres) {
                DB::statement("ALTER TABLE failed_jobs DISABLE ROW LEVEL SECURITY");
                Log::info('✓ Created failed_jobs table with RLS disabled');
            } else {
                Log::info('✓ Created failed_jobs table');
            }
        }

        // Create password_reset_tokens table if it doesn't exist
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
            
            if ($isPostgres) {
                DB::statement("ALTER TABLE password_reset_tokens DISABLE ROW LEVEL SECURITY");
                Log::info('✓ Created password_reset_tokens table with RLS disabled');
            } else {
                Log::info('✓ Created password_reset_tokens table');
            }
        }

        // Create personal_access_tokens table if it doesn't exist
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
            
            if ($isPostgres) {
                DB::statement("ALTER TABLE personal_access_tokens DISABLE ROW LEVEL SECURITY");
                Log::info('✓ Created personal_access_tokens table with RLS disabled');
            } else {
                Log::info('✓ Created personal_access_tokens table');
            }
        }

        // Fix RLS on all system tables (including existing ones) - PostgreSQL only
        if ($isPostgres) {
            $systemTables = [
                'cache',
                'cache_locks',
                'jobs',
                'job_batches',
                'failed_jobs',
                'sessions',
                'password_reset_tokens',
                'personal_access_tokens',
                'migrations'
            ];

            foreach ($systemTables as $table) {
                try {
                    // Check if table exists in public schema
                    $exists = DB::select("
                        SELECT EXISTS (
                            SELECT FROM information_schema.tables 
                            WHERE table_schema = 'public' 
                            AND table_name = ?
                        )
                    ", [$table]);

                    if ($exists[0]->exists) {
                        // Drop all policies first
                        $policies = DB::select("
                            SELECT policyname
                            FROM pg_policies
                            WHERE schemaname = 'public'
                            AND tablename = ?
                        ", [$table]);

                        foreach ($policies as $policy) {
                            DB::statement("DROP POLICY IF EXISTS \"{$policy->policyname}\" ON public.{$table}");
                        }

                        // Disable RLS
                        DB::statement("ALTER TABLE public.{$table} DISABLE ROW LEVEL SECURITY");
                        Log::info("✓ Fixed RLS on public.{$table}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to fix RLS on {$table}: " . $e->getMessage());
                }
            }
        }

        Log::info('=== Completed System Tables Setup ===');
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
    }
};
