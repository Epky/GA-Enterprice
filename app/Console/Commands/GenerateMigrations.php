<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MySQLSchemaAnalyzer;
use App\Services\LaravelMigrationGenerator;
use Exception;

class GenerateMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:generate {--file=GA-system-main/sql/water.sql : Path to SQL file} {--force : Overwrite existing migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Laravel migrations from MySQL schema for Supabase migration';

    /**
     * Execute the console command.
     */
    public function handle(MySQLSchemaAnalyzer $analyzer, LaravelMigrationGenerator $generator)
    {
        $this->info('🏗️  Generating Laravel Migrations for Supabase Migration');
        $this->newLine();
        
        $sqlFile = $this->option('file');
        $fullPath = base_path($sqlFile);
        
        if (!file_exists($fullPath)) {
            $this->error("❌ SQL file not found: {$fullPath}");
            return Command::FAILURE;
        }
        
        try {
            // Step 1: Analyze MySQL schema
            $this->info("📁 Analyzing MySQL schema from: {$sqlFile}");
            $analysis = $analyzer->parseSchemaFromFile($fullPath);
            
            // Step 2: Generate migrations
            $this->info("🔄 Generating Laravel migrations...");
            $migrations = $generator->generateMigrations($analysis);
            
            // Step 3: Check for existing migrations
            if (!$this->option('force')) {
                $existingMigrations = $this->checkExistingMigrations($migrations);
                if (!empty($existingMigrations)) {
                    $this->warn('⚠️  The following migrations already exist:');
                    foreach ($existingMigrations as $existing) {
                        $this->line("  • {$existing}");
                    }
                    
                    if (!$this->confirm('Do you want to overwrite existing migrations?')) {
                        $this->info('Migration generation cancelled.');
                        return Command::SUCCESS;
                    }
                }
            }
            
            // Step 4: Save migrations
            $this->info("💾 Saving migration files...");
            $savedFiles = $generator->saveMigrations($migrations);
            
            // Step 5: Display results
            $this->displayResults($migrations, $savedFiles);
            
            // Step 6: Show next steps
            $this->showNextSteps();
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('❌ Migration generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Check for existing migration files
     */
    private function checkExistingMigrations(array $migrations): array
    {
        $existing = [];
        
        foreach ($migrations as $migration) {
            $filePath = database_path('migrations/' . $migration['filename']);
            if (file_exists($filePath)) {
                $existing[] = $migration['filename'];
            }
        }
        
        return $existing;
    }
    
    /**
     * Display generation results
     */
    private function displayResults(array $migrations, array $savedFiles): void
    {
        $this->info('✅ Migration generation completed successfully!');
        $this->newLine();
        
        // Summary table
        $tableMigrations = array_filter($migrations, fn($m) => !isset($m['type']));
        $foreignKeyMigrations = array_filter($migrations, fn($m) => isset($m['type']) && $m['type'] === 'foreign_key');
        
        $this->table(
            ['Type', 'Count'],
            [
                ['Table Migrations', count($tableMigrations)],
                ['Foreign Key Migrations', count($foreignKeyMigrations)],
                ['Total Files Created', count($savedFiles)]
            ]
        );
        
        $this->newLine();
        
        // List generated migrations
        $this->info('📋 Generated Migration Files:');
        foreach ($migrations as $migration) {
            $type = isset($migration['type']) ? ' (Foreign Key)' : '';
            $this->line("  • {$migration['filename']}{$type}");
        }
        
        $this->newLine();
    }
    
    /**
     * Show next steps
     */
    private function showNextSteps(): void
    {
        $this->info('🎯 Next Steps:');
        $this->line('1. Review the generated migration files in database/migrations/');
        $this->line('2. Run: php artisan migrate:status (to check migration status)');
        $this->line('3. Run: php artisan migrate (to create tables in Supabase)');
        $this->line('4. Run: php artisan data:migrate (to migrate your existing data)');
        
        $this->newLine();
        $this->warn('⚠️  Important Notes:');
        $this->line('• Review each migration file before running migrate');
        $this->line('• Ensure your Supabase connection is working');
        $this->line('• Consider running migrations on a test database first');
        $this->line('• Backup your existing MySQL data before proceeding');
    }
}
