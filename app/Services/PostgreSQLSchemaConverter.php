<?php

namespace App\Services;

class PostgreSQLSchemaConverter
{
    private array $tables = [];
    private array $relationships = [];
    
    /**
     * Convert MySQL schema analysis to PostgreSQL CREATE statements
     */
    public function convertSchema(array $analysis): array
    {
        $this->tables = $analysis['tables'];
        $this->relationships = $analysis['relationships'];
        
        $postgresqlStatements = [];
        $dropStatements = [];
        
        // Generate DROP statements (for cleanup)
        foreach ($this->tables as $table) {
            $dropStatements[] = $this->generateDropStatement($table);
        }
        
        // Generate CREATE TABLE statements
        foreach ($this->tables as $table) {
            $postgresqlStatements[] = $this->generateCreateTableStatement($table);
        }
        
        // Generate ALTER statements for foreign keys
        $foreignKeyStatements = $this->generateForeignKeyStatements();
        
        // Generate CREATE INDEX statements
        $indexStatements = $this->generateIndexStatements();
        
        return [
            'drop_statements' => $dropStatements,
            'create_statements' => $postgresqlStatements,
            'foreign_key_statements' => $foreignKeyStatements,
            'index_statements' => $indexStatements,
            'complete_sql' => $this->generateCompleteSql($dropStatements, $postgresqlStatements, $foreignKeyStatements, $indexStatements)
        ];
    }
    
    /**
     * Generate DROP TABLE statement
     */
    private function generateDropStatement(array $table): string
    {
        return "DROP TABLE IF EXISTS {$table['laravel_name']} CASCADE;";
    }
    
    /**
     * Generate CREATE TABLE statement for PostgreSQL
     */
    private function generateCreateTableStatement(array $table): string
    {
        $tableName = $table['laravel_name'];
        $columns = [];
        
        foreach ($table['columns'] as $column) {
            $columns[] = $this->generateColumnDefinition($column, $table);
        }
        
        $sql = "CREATE TABLE {$tableName} (\n";
        $sql .= "    " . implode(",\n    ", $columns);
        
        // Add primary key constraint if exists
        if ($table['primary_key']) {
            $primaryKeyColumn = $this->findLaravelColumnName($table['primary_key'], $table);
            $sql .= ",\n    PRIMARY KEY ({$primaryKeyColumn})";
        }
        
        $sql .= "\n);";
        
        return $sql;
    }
    
    /**
     * Generate column definition for PostgreSQL
     */
    private function generateColumnDefinition(array $column, array $table): string
    {
        $definition = $column['laravel_name'] . ' ';
        
        // Handle auto-increment columns
        if ($column['auto_increment']) {
            $definition .= 'SERIAL';
        } else {
            $definition .= $this->convertDataType($column);
        }
        
        // Add NOT NULL constraint
        if (!$column['nullable'] && !$column['auto_increment']) {
            $definition .= ' NOT NULL';
        }
        
        // Add default value
        if ($column['default'] !== null && !$column['auto_increment']) {
            $default = $this->convertDefaultValue($column['default'], $column);
            $definition .= " DEFAULT {$default}";
        }
        
        // Add special defaults for timestamp columns
        if ($column['laravel_name'] === 'created_at' || $column['laravel_name'] === 'updated_at') {
            if ($column['laravel_name'] === 'created_at') {
                $definition .= ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $definition .= ' DEFAULT CURRENT_TIMESTAMP';
            }
        }
        
        return $definition;
    }
    
    /**
     * Convert MySQL data type to PostgreSQL
     */
    private function convertDataType(array $column): string
    {
        $mysqlType = strtolower($column['mysql_type']);
        
        // Special handling for specific columns
        if ($column['laravel_name'] === 'is_online') {
            return 'BOOLEAN';
        }
        
        // Data type conversions
        $conversions = [
            // Integer types
            '/^int\(\d+\)/' => 'INTEGER',
            '/^bigint\(\d+\)/' => 'BIGINT',
            '/^smallint\(\d+\)/' => 'SMALLINT',
            '/^tinyint\(1\)/' => 'BOOLEAN',
            '/^tinyint\(\d+\)/' => 'SMALLINT',
            
            // String types
            '/^varchar\((\d+)\)/' => 'VARCHAR($1)',
            '/^char\((\d+)\)/' => 'CHAR($1)',
            '/^text/' => 'TEXT',
            '/^longtext/' => 'TEXT',
            '/^mediumtext/' => 'TEXT',
            
            // Numeric types
            '/^decimal\((\d+),(\d+)\)/' => 'DECIMAL($1,$2)',
            '/^float/' => 'REAL',
            '/^double/' => 'DOUBLE PRECISION',
            
            // Date/Time types
            '/^timestamp/' => 'TIMESTAMP WITH TIME ZONE',
            '/^datetime/' => 'TIMESTAMP',
            '/^date/' => 'DATE',
            '/^time/' => 'TIME',
            
            // Other types
            '/^enum\((.*?)\)/' => 'VARCHAR(50)', // Will add CHECK constraint later
            '/^json/' => 'JSONB',
            '/^blob/' => 'BYTEA',
        ];
        
        foreach ($conversions as $pattern => $replacement) {
            if (preg_match($pattern, $mysqlType)) {
                return preg_replace($pattern, $replacement, $mysqlType);
            }
        }
        
        // Default fallback
        return 'TEXT';
    }
    
    /**
     * Convert MySQL default value to PostgreSQL
     */
    private function convertDefaultValue(string $default, array $column): string
    {
        // Handle special MySQL defaults
        $specialDefaults = [
            'current_timestamp()' => 'CURRENT_TIMESTAMP',
            'now()' => 'CURRENT_TIMESTAMP',
            '0000-00-00 00:00:00' => 'CURRENT_TIMESTAMP',
        ];
        
        $lowerDefault = strtolower($default);
        if (isset($specialDefaults[$lowerDefault])) {
            return $specialDefaults[$lowerDefault];
        }
        
        // Handle boolean values
        if ($column['postgresql_type'] === 'BOOLEAN') {
            return $default === '1' ? 'true' : 'false';
        }
        
        // Handle numeric values
        if (is_numeric($default)) {
            return $default;
        }
        
        // Handle string values
        return "'{$default}'";
    }
    
    /**
     * Generate foreign key constraint statements
     */
    private function generateForeignKeyStatements(): array
    {
        $statements = [];
        
        foreach ($this->relationships as $relationship) {
            if (isset($relationship['implied']) && $relationship['implied']) {
                continue; // Skip implied relationships for now
            }
            
            $tableName = $this->getTableLaravelName($relationship['table'] ?? '');
            $foreignKey = $this->convertColumnName($relationship['foreign_key']);
            $referencesTable = $this->getTableLaravelName($relationship['references_table']);
            $referencesColumn = $this->convertColumnName($relationship['references_column']);
            
            if ($tableName && $referencesTable) {
                $constraintName = "fk_{$tableName}_{$foreignKey}";
                $statements[] = "ALTER TABLE {$tableName} ADD CONSTRAINT {$constraintName} " .
                               "FOREIGN KEY ({$foreignKey}) REFERENCES {$referencesTable}({$referencesColumn});";
            }
        }
        
        // Add specific foreign keys based on our schema knowledge
        $knownRelationships = [
            ['table' => 'orders', 'column' => 'customer_id', 'references' => 'customers', 'ref_column' => 'id'],
            ['table' => 'inventory_logs', 'column' => 'product_id', 'references' => 'products', 'ref_column' => 'id'],
        ];
        
        foreach ($knownRelationships as $rel) {
            $constraintName = "fk_{$rel['table']}_{$rel['column']}";
            $statements[] = "ALTER TABLE {$rel['table']} ADD CONSTRAINT {$constraintName} " .
                           "FOREIGN KEY ({$rel['column']}) REFERENCES {$rel['references']}({$rel['ref_column']});";
        }
        
        return array_unique($statements);
    }
    
    /**
     * Generate index statements
     */
    private function generateIndexStatements(): array
    {
        $statements = [];
        
        // Create indexes for foreign key columns
        $foreignKeyIndexes = [
            ['table' => 'orders', 'column' => 'customer_id'],
            ['table' => 'inventory_logs', 'column' => 'product_id'],
        ];
        
        foreach ($foreignKeyIndexes as $index) {
            $indexName = "idx_{$index['table']}_{$index['column']}";
            $statements[] = "CREATE INDEX {$indexName} ON {$index['table']} ({$index['column']});";
        }
        
        // Create indexes for commonly queried columns
        $commonIndexes = [
            ['table' => 'customers', 'column' => 'email'],
            ['table' => 'admins', 'column' => 'username'],
            ['table' => 'orders', 'column' => 'created_at'],
            ['table' => 'contact_messages', 'column' => 'is_read'],
        ];
        
        foreach ($commonIndexes as $index) {
            $indexName = "idx_{$index['table']}_{$index['column']}";
            $statements[] = "CREATE INDEX {$indexName} ON {$index['table']} ({$index['column']});";
        }
        
        return $statements;
    }
    
    /**
     * Get Laravel table name from MySQL table name
     */
    private function getTableLaravelName(string $mysqlTableName): ?string
    {
        foreach ($this->tables as $table) {
            if ($table['name'] === $mysqlTableName) {
                return $table['laravel_name'];
            }
        }
        return null;
    }
    
    /**
     * Convert column name to Laravel convention
     */
    private function convertColumnName(string $columnName): string
    {
        $conversions = [
            'customerid' => 'customer_id',
            'admin_id' => 'id',
            'product_id' => 'id',
            'orders_id' => 'id',
            'delivery_id' => 'id',
            'log_id' => 'id',
        ];
        
        return $conversions[$columnName] ?? $columnName;
    }
    
    /**
     * Find Laravel column name in table
     */
    private function findLaravelColumnName(string $mysqlColumnName, array $table): string
    {
        foreach ($table['columns'] as $column) {
            if ($column['name'] === $mysqlColumnName) {
                return $column['laravel_name'];
            }
        }
        return $mysqlColumnName;
    }
    
    /**
     * Generate complete SQL script
     */
    private function generateCompleteSql(array $dropStatements, array $createStatements, array $foreignKeyStatements, array $indexStatements): string
    {
        $sql = "-- PostgreSQL Schema Migration for GA-Enterprise Water Delivery System\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "-- Drop existing tables\n";
        foreach ($dropStatements as $statement) {
            $sql .= $statement . "\n";
        }
        
        $sql .= "\n-- Create tables\n";
        foreach ($createStatements as $statement) {
            $sql .= $statement . "\n\n";
        }
        
        $sql .= "-- Add foreign key constraints\n";
        foreach ($foreignKeyStatements as $statement) {
            $sql .= $statement . "\n";
        }
        
        $sql .= "\n-- Create indexes\n";
        foreach ($indexStatements as $statement) {
            $sql .= $statement . "\n";
        }
        
        return $sql;
    }
    
    /**
     * Get conversion summary
     */
    public function getConversionSummary(array $analysis): array
    {
        $converted = $this->convertSchema($analysis);
        
        return [
            'original_tables' => count($analysis['tables']),
            'converted_tables' => count($converted['create_statements']),
            'foreign_keys' => count($converted['foreign_key_statements']),
            'indexes' => count($converted['index_statements']),
            'data_type_conversions' => $this->getDataTypeConversions($analysis['tables']),
        ];
    }
    
    /**
     * Get data type conversion summary
     */
    private function getDataTypeConversions(array $tables): array
    {
        $conversions = [];
        
        foreach ($tables as $table) {
            foreach ($table['columns'] as $column) {
                $mysqlType = $column['mysql_type'];
                $postgresType = $this->convertDataType($column);
                
                if (!isset($conversions[$mysqlType])) {
                    $conversions[$mysqlType] = [];
                }
                
                if (!in_array($postgresType, $conversions[$mysqlType])) {
                    $conversions[$mysqlType][] = $postgresType;
                }
            }
        }
        
        return $conversions;
    }
}