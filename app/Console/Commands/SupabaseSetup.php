<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SupabaseSetupService;

class SupabaseSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:setup {--check : Only check configuration without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup and configure Supabase connection for Laravel';

    /**
     * Execute the console command.
     */
    public function handle(SupabaseSetupService $supabaseService)
    {
        $this->info('🚀 Supabase Setup for GA-Enterprise Water Delivery System');
        $this->newLine();
        
        // Check configuration
        $this->info('1. Checking configuration...');
        $validation = $supabaseService->validateConfiguration();
        
        if (!$validation['valid']) {
            $this->error('❌ Configuration validation failed:');
            foreach ($validation['errors'] as $error) {
                $this->error("  • {$error}");
            }
            
            $this->newLine();
            $this->warn('Please complete your Supabase setup:');
            $this->line('1. Go to https://supabase.com and create a new project');
            $this->line('2. Get your project credentials from Settings > Database');
            $this->line('3. Update your .env file with the Supabase credentials');
            $this->newLine();
            $this->info('Required .env variables:');
            $this->line('SUPABASE_URL=https://your-project.supabase.co');
            $this->line('SUPABASE_ANON_KEY=your-anon-key');
            $this->line('SUPABASE_SERVICE_KEY=your-service-key');
            $this->line('SUPABASE_DB_HOST=db.your-project.supabase.co');
            $this->line('SUPABASE_DB_DATABASE=postgres');
            $this->line('SUPABASE_DB_USERNAME=postgres');
            $this->line('SUPABASE_DB_PASSWORD=your-database-password');
            
            return Command::FAILURE;
        }
        
        $this->info('✅ Configuration validation passed');
        
        // Test connection
        $this->info('2. Testing database connection...');
        $connectionTest = $supabaseService->testConnection();
        
        if (!$connectionTest['success']) {
            $this->error('❌ Connection test failed:');
            $this->error("  {$connectionTest['message']}");
            $this->error("  Error: {$connectionTest['error']}");
            return Command::FAILURE;
        }
        
        $this->info('✅ Successfully connected to Supabase database');
        $this->info("  PostgreSQL Version: {$connectionTest['details']['version']}");
        $this->info("  Server Time: {$connectionTest['details']['current_time']}");
        $this->info("  Can Create Tables: " . ($connectionTest['details']['can_create_tables'] ? 'Yes' : 'No'));
        
        if ($this->option('check')) {
            $this->info('✅ Configuration check completed successfully!');
            return Command::SUCCESS;
        }
        
        // Switch to Supabase as default connection
        $this->info('3. Configuring Laravel to use Supabase...');
        
        if ($this->confirm('Do you want to set Supabase as the default database connection?', true)) {
            $supabaseService->switchToSupabase();
            $this->info('✅ Supabase is now the default database connection');
            
            // Update .env file
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=supabase', $envContent);
            file_put_contents($envPath, $envContent);
            
            $this->info('✅ Updated .env file to use Supabase connection');
        }
        
        $this->newLine();
        $this->info('🎉 Supabase setup completed successfully!');
        $this->info('You can now run migrations and start using your Supabase database.');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Run: php artisan migrate:status');
        $this->line('2. Create your database migrations');
        $this->line('3. Run: php artisan migrate');
        
        return Command::SUCCESS;
    }
}
