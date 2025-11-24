<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MySQLSchemaAnalyzer;
use Exception;

class AnalyzeSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:analyze {--file=GA-system-main/sql/water.sql : Path to SQL file} {--output= : Output file for analysis report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze MySQL schema from SQL file and generate conversion report';

    /**
     * Execute the console command.
     */
    public function handle(MySQLSchemaAnalyzer $analyzer)
    {
        $this->info('🔍 Analyzing MySQL Schema for Supabase Migration');
        $this->newLine();
        
        $sqlFile = $this->option('file');
        $fullPath = base_path($sqlFile);
        
        if (!file_exists($fullPath)) {
            $this->error("❌ SQL file not found: {$fullPath}");
            $this->info('Available SQL files:');
            
            // Look for SQL files in common locations
            $searchPaths = [
                'GA-system-main/sql/',
                'database/',
                'storage/app/'
            ];
            
            foreach ($searchPaths as $path) {
                $searchPath = base_path($path);
                if (is_dir($searchPath)) {
                    $files = glob($searchPath . '*.sql');
                    foreach ($files as $file) {
                        $this->line('  • ' . str_replace(base_path() . '/', '', $file));
                    }
                }
            }
            
            return Command::FAILURE;
        }
        
        try {
            $this->info("📁 Analyzing file: {$sqlFile}");
            
            // Parse the schema
            $analysis = $analyzer->parseSchemaFromFile($fullPath);
            
            // Display summary
            $this->displaySummary($analysis['summary']);
            
            // Display tables
            $this->displayTables($analysis['tables']);
            
            // Display relationships
            $this->displayRelationships($analysis['relationships']);
            
            // Save detailed report if requested
            if ($outputFile = $this->option('output')) {
                $this->saveReport($analysis, $outputFile);
            }
            
            $this->newLine();
            $this->info('✅ Schema analysis completed successfully!');
            $this->info('Next step: Run "php artisan schema:convert" to generate Laravel migrations');
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('❌ Analysis failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display analysis summary
     */
    private function displaySummary(array $summary): void
    {
        $this->info('📊 Analysis Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tables', $summary['total_tables']],
                ['Total Columns', $summary['total_columns']],
                ['Relationships', $summary['total_relationships']],
                ['Indexes', $summary['total_indexes']],
                ['Tables with Timestamps', $summary['tables_with_timestamps']],
                ['Auto-increment Tables', $summary['auto_increment_tables']]
            ]
        );
        $this->newLine();
    }
    
    /**
     * Display table information
     */
    private function displayTables(array $tables): void
    {
        $this->info('📋 Tables Analysis:');
        
        $tableData = [];
        foreach ($tables as $table) {
            $tableData[] = [
                $table['name'],
                $table['laravel_name'],
                count($table['columns']),
                $table['primary_key'] ?? 'None',
                $table['auto_increment'] ?? 'None',
                $table['timestamps'] ? 'Yes' : 'No'
            ];
        }
        
        $this->table(
            ['MySQL Name', 'Laravel Name', 'Columns', 'Primary Key', 'Auto Increment', 'Timestamps'],
            $tableData
        );
        $this->newLine();
    }
    
    /**
     * Display relationships
     */
    private function displayRelationships(array $relationships): void
    {
        if (empty($relationships)) {
            $this->warn('⚠️  No foreign key relationships found');
            return;
        }
        
        $this->info('🔗 Relationships:');
        
        $relationshipData = [];
        foreach ($relationships as $rel) {
            $relationshipData[] = [
                $rel['table'] ?? 'N/A',
                $rel['foreign_key'],
                $rel['references_table'],
                $rel['references_column'],
                isset($rel['implied']) ? 'Implied' : 'Explicit'
            ];
        }
        
        $this->table(
            ['Table', 'Foreign Key', 'References Table', 'References Column', 'Type'],
            $relationshipData
        );
        $this->newLine();
    }
    
    /**
     * Save detailed report to file
     */
    private function saveReport(array $analysis, string $outputFile): void
    {
        $reportPath = base_path($outputFile);
        $reportContent = json_encode($analysis, JSON_PRETTY_PRINT);
        
        file_put_contents($reportPath, $reportContent);
        $this->info("📄 Detailed report saved to: {$outputFile}");
    }
}
