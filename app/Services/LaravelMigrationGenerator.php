<?php

namespace App\Services;

use Illuminate\Support\Str;

class LaravelMigrationGenerator
{
    private array $tables = [];
    private array $relationships = [];
    private int $migrationCounter = 1;
    
    /**
     * Generate Laravel migrations from schema analysis
     */
    public function generateMigrations(array $analysis): array
    {
        $this->tables = $analysis['tables'];
        $this->relationships = $analysis['relationships'];
        
        $migrations = [];
        
        // Generate table creation migrations in dependency order
        $tableOrder = $this->getTableCreationOrder();
        
        foreach ($tableOrder as $tableName) {
            if (isset($this->tables[$tableName])) {
                $migrations[] = $this->generateTableMigration($this->tables[$tableName]);
            }
        }
        
        // Generate foreign key migrations
        $foreignKeyMigrations = $this->generateForeignKeyMigrations();
        $migrations = array_merge($migrations, $foreignKeyMigrations);
        
        return $migrations;
    }
    
    /**
     * Get table creation order based on dependencies
     */
    private function getTableCreationOrder(): array
    {
        // Define the order based on dependencies
        // Tables with no dependencies first, then tables that reference them
        return [
            'tbl_admin_account',      // No dependencies
            'tbl_customer_account',   // No dependencies  
            'tbl_type_delivery',      // No dependencies
            'tbl_product',            // No dependencies
            'tbl_contact_messages',   // No dependencies
            'tbl_orders',             // References customers
            'inventory_log'           // References products
        ];
    }
    
    /**
     * Generate migration for a single table
     */
    private function generateTableMigration(array $table): array
    {
        $className = 'Create' . Str::studly($table['laravel_name']) . 'Table';
        $tableName = $table['laravel_name'];
        $timestamp = $this->generateTimestamp();
        $filename = $timestamp . '_create_' . $tableName . '_table.php';
        
        $migrationContent = $this->generateMigrationContent($className, $table);
        
        return [
            'filename' => $filename,
            'class_name' => $className,
            'table_name' => $tableName,
            'content' => $migrationContent,
            'timestamp' => $timestamp
        ];
    }
    
    /**
     * Generate migration file content
     */
    private function generateMigrationContent(string $className, array $table): string
    {
        $tableName = $table['laravel_name'];
        $columns = $this->generateColumnDefinitions($table);
        
        return "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
{$columns}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};";
    }
    
    /**
     * Generate column definitions for Laravel migration
     */
    private function generateColumnDefinitions(array $table): string
    {
        $columns = [];
        $hasTimestamps = false;
        
        foreach ($table['columns'] as $column) {
            $laravelColumn = $this->generateLaravelColumn($column, $table);
            
            if ($laravelColumn) {
                // Check if this is a timestamp column that Laravel handles automatically
                if (in_array($column['laravel_name'], ['created_at', 'updated_at'])) {
                    $hasTimestamps = true;
                    continue; // Skip individual timestamp columns
                }
                
                $columns[] = $laravelColumn;
            }
        }
        
        // Add Laravel timestamps if the table has timestamp columns
        if ($hasTimestamps || $table['timestamps']) {
            $columns[] = '            $table->timestamps();';
        }
        
        return implode("\n", $columns);
    }
    
    /**
     * Generate Laravel column definition
     */
    private function generateLaravelColumn(array $column, array $table): ?string
    {
        $name = $column['laravel_name'];
        $definition = '            ';
        
        // Handle auto-increment primary key
        if ($column['auto_increment'] && $name === 'id') {
            return '$table->id();';
        }
        
        // Handle different column types
        $definition .= $this->getLaravelColumnType($column);
        
        // Add nullable
        if ($column['nullable'] && !$column['auto_increment']) {
            $definition .= '->nullable()';
        }
        
        // Add default value
        if ($column['default'] !== null && !$column['auto_increment']) {
            $default = $this->formatDefaultValue($column['default'], $column);
            $definition .= "->default({$default})";
        }
        
        // Add comment for original MySQL column name if different
        if ($column['name'] !== $column['laravel_name']) {
            $definition .= "->comment('Original: {$column['name']}')";
        }
        
        $definition .= ';';
        
        return $definition;
    }
    
    /**
     * Get Laravel column type method
     */
    private function getLaravelColumnType(array $column): string
    {
        $name = $column['laravel_name'];
        $mysqlType = strtolower($column['mysql_type']);
        
        // Special handling for specific columns
        if ($name === 'email') {
            return "\$table->string('{$name}')->unique()";
        }
        
        if ($name === 'password') {
            return "\$table->string('{$name}')";
        }
        
        if ($name === 'is_online' || $name === 'is_read') {
            return "\$table->boolean('{$name}')";
        }
        
        // Handle different MySQL types
        if (preg_match('/^int\\(\\d+\\)/', $mysqlType)) {
            if ($column['auto_increment']) {
                return "\$table->id('{$name}')";
            }
            return "\$table->integer('{$name}')";
        }
        
        if (preg_match('/^varchar\\((\\d+)\\)/', $mysqlType, $matches)) {
            $length = $matches[1];
            return "\$table->string('{$name}', {$length})";
        }
        
        if (preg_match('/^decimal\\((\\d+),(\\d+)\\)/', $mysqlType, $matches)) {
            $precision = $matches[1];
            $scale = $matches[2];
            return "\$table->decimal('{$name}', {$precision}, {$scale})";
        }
        
        if (preg_match('/^text/', $mysqlType)) {
            return "\$table->text('{$name}')";
        }
        
        if (preg_match('/^timestamp/', $mysqlType)) {
            return "\$table->timestamp('{$name}')";
        }
        
        if (preg_match('/^tinyint\\(1\\)/', $mysqlType)) {
            return "\$table->boolean('{$name}')";
        }
        
        // Default to string
        return "\$table->string('{$name}')";
    }
    
    /**
     * Format default value for Laravel migration
     */
    private function formatDefaultValue(string $default, array $column): string
    {
        // Handle boolean values
        if ($column['postgresql_type'] === 'BOOLEAN' || strpos($column['mysql_type'], 'tinyint(1)') !== false) {
            return $default === '1' ? 'true' : 'false';
        }
        
        // Handle numeric values
        if (is_numeric($default)) {
            return $default;
        }
        
        // Handle special values
        if (strtolower($default) === 'current_timestamp') {
            return 'DB::raw(\"CURRENT_TIMESTAMP\")';
        }
        
        // Handle string values
        return "'{$default}'";
    }
    
    /**
     * Generate foreign key migrations
     */
    private function generateForeignKeyMigrations(): array
    {
        $migrations = [];
        
        // Define known foreign key relationships
        $foreignKeys = [
            [
                'table' => 'orders',
                'column' => 'customer_id', 
                'references' => 'customers',
                'on_column' => 'id'
            ]
        ];
        
        foreach ($foreignKeys as $fk) {
            $className = 'Add' . Str::studly($fk['column']) . 'ForeignKeyTo' . Str::studly($fk['table']) . 'Table';
            $timestamp = $this->generateTimestamp();
            $filename = $timestamp . '_add_' . $fk['column'] . '_foreign_key_to_' . $fk['table'] . '_table.php';
            
            $content = $this->generateForeignKeyMigrationContent($className, $fk);
            
            $migrations[] = [
                'filename' => $filename,
                'class_name' => $className,
                'table_name' => $fk['table'],
                'content' => $content,
                'timestamp' => $timestamp,
                'type' => 'foreign_key'
            ];
        }
        
        return $migrations;
    }
    
    /**
     * Generate foreign key migration content
     */
    private function generateForeignKeyMigrationContent(string $className, array $foreignKey): string
    {
        $table = $foreignKey['table'];
        $column = $foreignKey['column'];
        $references = $foreignKey['references'];
        $onColumn = $foreignKey['on_column'];
        
        return "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->foreign('{$column}')->references('{$onColumn}')->on('{$references}')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropForeign(['{$column}']);
        });
    }
};";
    }
    
    /**
     * Generate timestamp for migration
     */
    private function generateTimestamp(): string
    {
        $baseTime = now()->format('Y_m_d_His');
        $timestamp = $baseTime . sprintf('%02d', $this->migrationCounter);
        $this->migrationCounter++;
        return $timestamp;
    }
    
    /**
     * Save migrations to files
     */
    public function saveMigrations(array $migrations): array
    {
        $savedFiles = [];
        
        foreach ($migrations as $migration) {
            $filePath = database_path('migrations/' . $migration['filename']);
            file_put_contents($filePath, $migration['content']);
            $savedFiles[] = $filePath;
        }
        
        return $savedFiles;
    }
}