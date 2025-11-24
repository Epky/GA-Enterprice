<?php

namespace App\Services;

use Exception;

class MySQLSchemaAnalyzer
{
    private array $tables = [];
    private array $relationships = [];
    private array $indexes = [];
    
    /**
     * Parse MySQL schema from SQL file
     */
    public function parseSchemaFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("SQL file not found: {$filePath}");
        }
        
        $sqlContent = file_get_contents($filePath);
        return $this->parseSchema($sqlContent);
    }
    
    /**
     * Parse MySQL schema from SQL string
     */
    public function parseSchema(string $sqlContent): array
    {
        // Clean up the SQL content
        $sqlContent = $this->cleanSqlContent($sqlContent);
        
        // Extract CREATE TABLE statements
        $this->extractTables($sqlContent);
        
        // Extract foreign key relationships
        $this->extractRelationships($sqlContent);
        
        // Extract indexes
        $this->extractIndexes($sqlContent);
        
        return [
            'tables' => $this->tables,
            'relationships' => $this->relationships,
            'indexes' => $this->indexes,
            'summary' => $this->generateSummary()
        ];
    }
    
    /**
     * Clean SQL content for parsing
     */
    private function cleanSqlContent(string $sql): string
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Remove MySQL-specific commands
        $sql = preg_replace('/SET\s+.*?;/i', '', $sql);
        $sql = preg_replace('/START\s+TRANSACTION;/i', '', $sql);
        $sql = preg_replace('/COMMIT;/i', '', $sql);
        $sql = preg_replace('/!\d+.*?\s+/i', '', $sql);
        
        return $sql;
    }
    
    /**
     * Extract table definitions
     */
    private function extractTables(string $sql): void
    {
        // Match CREATE TABLE statements
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?\s*\((.*?)\)\s*ENGINE\s*=\s*\w+.*?;/is', $sql, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $tableName = $match[1];
            $tableDefinition = $match[2];
            
            $this->tables[$tableName] = [
                'name' => $tableName,
                'laravel_name' => $this->convertToLaravelTableName($tableName),
                'columns' => $this->parseColumns($tableDefinition),
                'primary_key' => $this->findPrimaryKey($tableDefinition),
                'auto_increment' => $this->findAutoIncrement($tableDefinition),
                'timestamps' => $this->hasTimestamps($tableDefinition)
            ];
        }
    }
    
    /**
     * Parse table columns
     */
    private function parseColumns(string $tableDefinition): array
    {
        $columns = [];
        $lines = explode(',', $tableDefinition);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip constraints and keys
            if (preg_match('/^\s*(PRIMARY\s+KEY|KEY|UNIQUE|CONSTRAINT|FOREIGN\s+KEY)/i', $line)) {
                continue;
            }
            
            // Parse column definition
            if (preg_match('/^\s*`?(\w+)`?\s+(.+)$/i', $line, $matches)) {
                $columnName = $matches[1];
                $columnDefinition = trim($matches[2]);
                
                $columns[$columnName] = [
                    'name' => $columnName,
                    'laravel_name' => $this->convertToLaravelColumnName($columnName),
                    'mysql_type' => $this->extractDataType($columnDefinition),
                    'postgresql_type' => $this->convertToPostgreSQLType($columnDefinition),
                    'nullable' => !preg_match('/NOT\s+NULL/i', $columnDefinition),
                    'default' => $this->extractDefault($columnDefinition),
                    'auto_increment' => preg_match('/AUTO_INCREMENT/i', $columnDefinition),
                    'definition' => $columnDefinition
                ];
            }
        }
        
        return $columns;
    }
    
    /**
     * Convert MySQL table name to Laravel convention
     */
    private function convertToLaravelTableName(string $tableName): string
    {
        // Convert tbl_admin_account to admins
        $conversions = [
            'tbl_admin_account' => 'admins',
            'tbl_customer_account' => 'customers',
            'tbl_product' => 'products',
            'tbl_orders' => 'orders',
            'tbl_order' => 'orders', // Handle both variations
            'tbl_contact_messages' => 'contact_messages',
            'tbl_type_delivery' => 'delivery_types',
            'inventory_log' => 'inventory_logs'
        ];
        
        return $conversions[$tableName] ?? str_replace('tbl_', '', $tableName);
    }
    
    /**
     * Convert MySQL column name to Laravel convention
     */
    private function convertToLaravelColumnName(string $columnName): string
    {
        $conversions = [
            'admin_id' => 'id',
            'customerid' => 'id',
            'product_id' => 'id',
            'orders_id' => 'id',
            'order_id' => 'id',
            'delivery_id' => 'id',
            'log_id' => 'id',
            'admin_name' => 'first_name',
            'admin_mname' => 'middle_name',
            'admin_lname' => 'last_name',
            'admin_username' => 'username',
            'admin_password' => 'password',
            'customer_name' => 'name',
            'customer_number' => 'phone',
            'customer_email' => 'email',
            'customer_password' => 'password',
            'online_offline_status' => 'is_online',
            'date_register' => 'created_at',
            'date_added' => 'created_at',
            'date_submitted' => 'created_at'
        ];
        
        return $conversions[$columnName] ?? $columnName;
    }
    
    /**
     * Extract data type from column definition
     */
    private function extractDataType(string $definition): string
    {
        if (preg_match('/^(\w+)(\([^)]+\))?/i', $definition, $matches)) {
            return strtolower($matches[1]) . ($matches[2] ?? '');
        }
        return 'unknown';
    }
    
    /**
     * Convert MySQL data type to PostgreSQL
     */
    private function convertToPostgreSQLType(string $definition): string
    {
        $definition = strtolower($definition);
        
        // Auto increment handling
        if (preg_match('/int.*auto_increment/i', $definition)) {
            return 'SERIAL';
        }
        
        // Data type conversions
        $conversions = [
            '/^int\(\d+\)/' => 'INTEGER',
            '/^bigint\(\d+\)/' => 'BIGINT',
            '/^varchar\((\d+)\)/' => 'VARCHAR($1)',
            '/^text/' => 'TEXT',
            '/^decimal\((\d+),(\d+)\)/' => 'DECIMAL($1,$2)',
            '/^timestamp/' => 'TIMESTAMP WITH TIME ZONE',
            '/^datetime/' => 'TIMESTAMP',
            '/^tinyint\(1\)/' => 'BOOLEAN',
            '/^enum\((.*?)\)/' => 'VARCHAR(50)', // Will add CHECK constraint
        ];
        
        foreach ($conversions as $pattern => $replacement) {
            if (preg_match($pattern, $definition)) {
                return preg_replace($pattern, $replacement, $definition);
            }
        }
        
        return 'TEXT'; // Default fallback
    }
    
    /**
     * Extract default value
     */
    private function extractDefault(string $definition): ?string
    {
        if (preg_match('/DEFAULT\s+([^,\s]+)/i', $definition, $matches)) {
            $default = trim($matches[1], "'\"");
            
            // Handle special MySQL defaults
            if (strtolower($default) === 'current_timestamp()') {
                return 'CURRENT_TIMESTAMP';
            }
            
            return $default === 'NULL' ? null : $default;
        }
        
        return null;
    }
    
    /**
     * Find primary key
     */
    private function findPrimaryKey(string $tableDefinition): ?string
    {
        if (preg_match('/PRIMARY\s+KEY\s*\(\s*`?(\w+)`?\s*\)/i', $tableDefinition, $matches)) {
            return $matches[1];
        }
        
        // Check for inline primary key
        if (preg_match('/`?(\w+)`?\s+.*?PRIMARY\s+KEY/i', $tableDefinition, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Find auto increment column
     */
    private function findAutoIncrement(string $tableDefinition): ?string
    {
        if (preg_match('/`?(\w+)`?\s+.*?AUTO_INCREMENT/i', $tableDefinition, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if table has timestamp columns
     */
    private function hasTimestamps(string $tableDefinition): bool
    {
        return preg_match('/(created_at|updated_at|date_register|date_added)/i', $tableDefinition);
    }
    
    /**
     * Extract foreign key relationships
     */
    private function extractRelationships(string $sql): void
    {
        // Extract FOREIGN KEY constraints
        preg_match_all('/FOREIGN\s+KEY\s*\(\s*`?(\w+)`?\s*\)\s+REFERENCES\s+`?(\w+)`?\s*\(\s*`?(\w+)`?\s*\)/i', $sql, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $this->relationships[] = [
                'foreign_key' => $match[1],
                'references_table' => $match[2],
                'references_column' => $match[3]
            ];
        }
        
        // Also check for implied relationships based on column names
        foreach ($this->tables as $tableName => $table) {
            foreach ($table['columns'] as $column) {
                if (preg_match('/(\w+)id$/', $column['name'], $matches) && $matches[1] !== $tableName) {
                    $referencedTable = $matches[1];
                    if (isset($this->tables["tbl_{$referencedTable}_account"]) || isset($this->tables["tbl_{$referencedTable}"])) {
                        $this->relationships[] = [
                            'table' => $tableName,
                            'foreign_key' => $column['name'],
                            'references_table' => "tbl_{$referencedTable}_account",
                            'references_column' => $column['name'],
                            'implied' => true
                        ];
                    }
                }
            }
        }
    }
    
    /**
     * Extract indexes
     */
    private function extractIndexes(string $sql): void
    {
        // Extract ALTER TABLE ADD INDEX statements
        preg_match_all('/ALTER\s+TABLE\s+`?(\w+)`?\s+ADD\s+(?:PRIMARY\s+KEY|KEY|INDEX)\s*(?:`?(\w+)`?)?\s*\(\s*`?(\w+)`?\s*\)/i', $sql, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $this->indexes[] = [
                'table' => $match[1],
                'name' => $match[2] ?? null,
                'column' => $match[3],
                'type' => 'INDEX'
            ];
        }
    }
    
    /**
     * Generate analysis summary
     */
    private function generateSummary(): array
    {
        return [
            'total_tables' => count($this->tables),
            'total_columns' => array_sum(array_map(fn($table) => count($table['columns']), $this->tables)),
            'total_relationships' => count($this->relationships),
            'total_indexes' => count($this->indexes),
            'tables_with_timestamps' => count(array_filter($this->tables, fn($table) => $table['timestamps'])),
            'auto_increment_tables' => count(array_filter($this->tables, fn($table) => $table['auto_increment'] !== null))
        ];
    }
    
    /**
     * Get analysis report
     */
    public function getAnalysisReport(): array
    {
        return [
            'tables' => $this->tables,
            'relationships' => $this->relationships,
            'indexes' => $this->indexes,
            'summary' => $this->generateSummary()
        ];
    }
}