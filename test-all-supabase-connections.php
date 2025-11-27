<?php

/**
 * Comprehensive Supabase Connection Tester
 * This will test all possible connection methods and find the working one
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🔧 COMPREHENSIVE SUPABASE CONNECTION TEST\n";
echo "==========================================\n\n";

$projectRef = 'hgmdtzpsbzwanjuhiemf';
$password = $_ENV['DB_PASSWORD'];
$database = 'postgres';

// Test configurations
$configs = [
    'Transaction Pooler (6543) - postgres.ref format' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '6543',
        'username' => "postgres.{$projectRef}",
    ],
    'Session Pooler (5432) - postgres.ref format' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
        'username' => "postgres.{$projectRef}",
    ],
    'Transaction Pooler (6543) - postgres format' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '6543',
        'username' => 'postgres',
    ],
    'Session Pooler (5432) - postgres format' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
        'username' => 'postgres',
    ],
    'Direct Connection (6543) - postgres.ref format' => [
        'host' => "db.{$projectRef}.supabase.co",
        'port' => '6543',
        'username' => "postgres.{$projectRef}",
    ],
    'Direct Connection (5432) - postgres.ref format' => [
        'host' => "db.{$projectRef}.supabase.co",
        'port' => '5432',
        'username' => "postgres.{$projectRef}",
    ],
];

$workingConfig = null;

foreach ($configs as $name => $config) {
    echo "Testing: {$name}\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    echo "  Username: {$config['username']}\n";
    
    try {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$database};sslmode=require";
        $pdo = new PDO($dsn, $config['username'], $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        
        // Try to query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  ✅ SUCCESS! Connected and queried database.\n";
        echo "  📊 Categories count: {$result['count']}\n";
        
        $workingConfig = [
            'name' => $name,
            'config' => $config
        ];
        
        echo "\n";
        break; // Stop at first working connection
        
    } catch (PDOException $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

if ($workingConfig) {
    echo "🎉 FOUND WORKING CONNECTION!\n";
    echo "============================\n\n";
    echo "Update your .env file with these settings:\n\n";
    echo "DB_CONNECTION=pgsql\n";
    echo "DB_HOST={$workingConfig['config']['host']}\n";
    echo "DB_PORT={$workingConfig['config']['port']}\n";
    echo "DB_DATABASE={$database}\n";
    echo "DB_USERNAME={$workingConfig['config']['username']}\n";
    echo "DB_PASSWORD={$password}\n";
    echo "DB_SSLMODE=require\n";
    
} else {
    echo "❌ NO WORKING CONNECTION FOUND\n";
    echo "================================\n\n";
    echo "Possible issues:\n";
    echo "1. Wrong database password\n";
    echo "2. Connection pooling not enabled in Supabase\n";
    echo "3. Firewall blocking connections\n";
    echo "4. IPv6 connectivity issues\n\n";
    echo "Next steps:\n";
    echo "1. Go to Supabase Dashboard: https://supabase.com/dashboard/project/{$projectRef}/settings/database\n";
    echo "2. Reset your database password\n";
    echo "3. Enable connection pooling if not enabled\n";
    echo "4. Copy the exact connection string from the dashboard\n";
}
