<?php

/**
 * Supabase Connection Test Script
 * Run this to test different connection methods
 */

echo "=== Supabase Connection Test ===\n\n";

// Test configurations
$configs = [
    'Direct Connection (IPv6)' => [
        'host' => 'db.hgmdtzpsbzwanjuhiemf.supabase.co',
        'port' => '5432',
        'dbname' => 'postgres',
        'user' => 'postgres',
        'password' => 'edselsuraltapayan26',
        'sslmode' => 'require',
    ],
    'Pooler - Transaction Mode' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '6543',
        'dbname' => 'postgres',
        'user' => 'postgres.hgmdtzpsbzwanjuhiemf',
        'password' => 'edselsuraltapayan26',
        'sslmode' => 'require',
    ],
    'Pooler - Session Mode' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
        'dbname' => 'postgres',
        'user' => 'postgres.hgmdtzpsbzwanjuhiemf',
        'password' => 'edselsuraltapayan26',
        'sslmode' => 'require',
    ],
];

foreach ($configs as $name => $config) {
    echo "Testing: $name\n";
    echo str_repeat('-', 50) . "\n";
    
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['sslmode']
    );
    
    echo "DSN: $dsn\n";
    echo "User: {$config['user']}\n";
    
    try {
        $pdo = new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ]
        );
        
        $result = $pdo->query('SELECT version()')->fetch(PDO::FETCH_ASSOC);
        echo "✓ SUCCESS!\n";
        echo "PostgreSQL Version: " . substr($result['version'], 0, 50) . "...\n";
        
        // Test a simple query
        $result = $pdo->query('SELECT current_database(), current_user')->fetch(PDO::FETCH_ASSOC);
        echo "Database: {$result['current_database']}\n";
        echo "User: {$result['current_user']}\n";
        
    } catch (PDOException $e) {
        echo "✗ FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Test Complete ===\n";
echo "\nNext Steps:\n";
echo "1. If 'Direct Connection' works: Your system supports IPv6\n";
echo "2. If 'Pooler' works: Use that configuration in your .env\n";
echo "3. If none work: Check your Supabase dashboard for correct credentials\n";
echo "\nTo get pooler connection string:\n";
echo "1. Go to: https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf/settings/database\n";
echo "2. Scroll to 'Connection Pooling' section\n";
echo "3. Copy the connection string\n";
