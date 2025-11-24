<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSupabaseRLS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:check-rls {--fix : Automatically fix unrestricted tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and optionally fix Row Level Security on all tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Row Level Security (RLS) status for all tables...');
        $this->newLine();
        
        // Get all tables in the public schema
        $tables = DB::select("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = 'public'
            ORDER BY tablename
        ");
        
        $unrestrictedTables = [];
        $restrictedTables = [];
        
        foreach ($tables as $table) {
            $tableName = $table->tablename;
            
            // Check if RLS is enabled
            $rlsStatus = DB::select("
                SELECT relrowsecurity 
                FROM pg_class 
                WHERE relname = ? AND relkind = 'r'
            ", [$tableName]);
            
            if (empty($rlsStatus) || !$rlsStatus[0]->relrowsecurity) {
                $unrestrictedTables[] = $tableName;
            } else {
                $restrictedTables[] = $tableName;
            }
        }
        
        // Display results
        $this->info("📊 RLS Status Summary:");
        $this->table(
            ['Status', 'Count', 'Tables'],
            [
                ['✅ RLS Enabled', count($restrictedTables), implode(', ', array_slice($restrictedTables, 0, 5)) . (count($restrictedTables) > 5 ? '...' : '')],
                ['❌ Unrestricted', count($unrestrictedTables), implode(', ', $unrestrictedTables)]
            ]
        );
        
        if (empty($unrestrictedTables)) {
            $this->info('🎉 All tables have Row Level Security enabled!');
            return Command::SUCCESS;
        }
        
        $this->newLine();
        $this->warn('⚠️  Found ' . count($unrestrictedTables) . ' unrestricted tables:');
        foreach ($unrestrictedTables as $table) {
            $this->line("  • {$table}");
        }
        
        if ($this->option('fix')) {
            $this->fixUnrestrictedTables($unrestrictedTables);
        } else {
            $this->newLine();
            $this->info('💡 To automatically fix these tables, run:');
            $this->line('php artisan supabase:check-rls --fix');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Fix unrestricted tables by enabling RLS and adding service_role policy
     */
    private function fixUnrestrictedTables(array $tables): void
    {
        $this->newLine();
        $this->info('🔧 Fixing unrestricted tables...');
        
        foreach ($tables as $table) {
            try {
                $this->line("Fixing: {$table}");
                
                // Enable RLS
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                
                // Add service_role policy
                DB::statement("
                    CREATE POLICY service_role_all_access ON {$table}
                    FOR ALL TO service_role
                    USING (true)
                    WITH CHECK (true)
                ");
                
                $this->info("  ✅ Fixed: {$table}");
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to fix {$table}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info('🎉 All tables have been secured with Row Level Security!');
    }
}