<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class TestSupabaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Supabase database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Supabase database connection...');
        
        try {
            // Test connection using supabase configuration
            $connection = DB::connection('supabase');
            
            // Test basic query
            $result = $connection->select('SELECT version() as version');
            
            if ($result) {
                $this->info('✅ Successfully connected to Supabase!');
                $this->info('PostgreSQL Version: ' . $result[0]->version);
                
                // Test if we can create a simple table
                $this->info('Testing table creation permissions...');
                
                $connection->statement('CREATE TABLE IF NOT EXISTS connection_test (id SERIAL PRIMARY KEY, created_at TIMESTAMP DEFAULT NOW())');
                $connection->statement('DROP TABLE IF EXISTS connection_test');
                
                $this->info('✅ Database permissions are working correctly!');
                
                return Command::SUCCESS;
            }
            
        } catch (Exception $e) {
            $this->error('❌ Failed to connect to Supabase database:');
            $this->error($e->getMessage());
            
            $this->newLine();
            $this->warn('Please check your Supabase configuration in .env file:');
            $this->line('- SUPABASE_DB_HOST');
            $this->line('- SUPABASE_DB_DATABASE');
            $this->line('- SUPABASE_DB_USERNAME');
            $this->line('- SUPABASE_DB_PASSWORD');
            
            return Command::FAILURE;
        }
    }
}
