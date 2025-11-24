<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MySQLSchemaAnalyzer;
use App\Services\PostgreSQLSchemaConverter;
use Exception;

class ConvertSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:convert {--file=GA-system-main/sql/water.sql : Path to SQL file} {--output=database/schema/postgresql.sql : Output file for PostgreSQL schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert MySQL schema to PostgreSQL for Supabase migration';

    /**
     * Execute the console command.
     */
    public function handle(MySQLSchemaAnalyzer $analyzer, PostgreSQLSchemaConverter $converter)
    {
        $this->info('🔄 Converting MySQL Schema to PostgreSQL for Supabase');
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
            
            $this->info("✅ Found {$analysis['summary']['total_tables']} tables with {$analysis['summary']['total_columns']} columns");
            
            // Step 2: Convert to PostgreSQL
            $this->info("🔄 Converting to PostgreSQL schema...");
            $conversion = $converter->convertSchema($analysis);
            
            // Step 3: Display conversion summary
            $this->displayConversionSummary($conversion);
            
            // Step 4: Save PostgreSQL schema
            $outputFile = $this->option('output');
            $this->savePostgreSQLSchema($conversion, $outputFile);
            
            // Step 5: Show next steps
            $this->showNextSteps();
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('❌ Conversion failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display conversion summary
     */
    private function displayConversionSummary(array $conversion): void
    {
        $this->info('📊 Conversion Summary:');
        
        $this->table(
            ['Component', 'Count'],
            [
                ['Tables Created', count($conversion['create_statements'])],
                ['Foreign Keys', count($conversion['foreign_key_statements'])],
                ['Indexes', count($conversion['index_statements'])],
                ['Drop Statements', count($conversion['drop_statements'])]
            ]
        );
        
        $this->newLine();
        
        // Show table conversions
        $this->info('📋 Table Name Conversions:');
        $this->line('MySQL Table → PostgreSQL Table');
        $this->line('─────────────────────────────────');
        $this->line('tbl_admin_account → admins');
        $this->line('tbl_customer_account → customers');
        $this->line('tbl_product → products');
        $this->line('tbl_orders → orders');
        $this->line('tbl_contact_messages → contact_messages');
        $this->line('tbl_type_delivery → delivery_types');
        $this->line('inventory_log → inventory_logs');
        
        $this->newLine();
    }
    
    /**
     * Save PostgreSQL schema to file
     */
    private function savePostgreSQLSchema(array $conversion, string $outputFile): void
    {
        $outputPath = base_path($outputFile);
        $outputDir = dirname($outputPath);
        
        // Create directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Save the complete SQL
        file_put_contents($outputPath, $conversion['complete_sql']);
        
        $this->info("💾 PostgreSQL schema saved to: {$outputFile}");
        
        // Also save individual components
        $componentsDir = $outputDir . '/components';
        if (!is_dir($componentsDir)) {
            mkdir($componentsDir, 0755, true);
        }
        
        file_put_contents($componentsDir . '/create_tables.sql', implode("\n\n", $conversion['create_statements']));
        file_put_contents($componentsDir . '/foreign_keys.sql', implode("\n", $conversion['foreign_key_statements']));
        file_put_contents($componentsDir . '/indexes.sql', implode("\n", $conversion['index_statements']));
        file_put_contents($componentsDir . '/drop_tables.sql', implode("\n", $conversion['drop_statements']));
        
        $this->info("📁 Individual components saved to: database/schema/components/");
    }
    
    /**
     * Show next steps
     */
    private function showNextSteps(): void
    {
        $this->newLine();
        $this->info('🎯 Next Steps:');
        $this->line('1. Review the generated PostgreSQL schema');
        $this->line('2. Run: php artisan migrate:generate (to create Laravel migrations)');
        $this->line('3. Run: php artisan migrate (to create tables in Supabase)');
        $this->line('4. Run: php artisan data:migrate (to migrate your data)');
        
        $this->newLine();
        $this->warn('⚠️  Important Notes:');
        $this->line('• Review the schema before running migrations');
        $this->line('• Backup your existing data before migration');
        $this->line('• Test the conversion with sample data first');
    }
}
