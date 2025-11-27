<?php
/**
 * Disable RLS Migrations for MySQL
 * RLS (Row Level Security) is PostgreSQL-specific and not supported in MySQL
 */

$rlsMigrations = [
    'database/migrations/2025_11_04_090743_enable_beauty_store_rls_policies.php',
    'database/migrations/2025_11_04_091844_fix_migrations_table_rls.php',
    'database/migrations/2025_11_04_100320_disable_rls_on_laravel_system_tables.php',
    'database/migrations/2025_11_04_120000_comprehensive_rls_fix_for_system_tables.php',
    'database/migrations/2025_11_06_120000_create_users_table_with_rls.php',
    'database/migrations/2025_11_28_000001_fix_public_table_rls_policies.php',
    'database/migrations/2025_11_28_100000_enable_rls_all_tables.php',
    'database/migrations/2025_11_28_120000_fix_users_table_rls_for_authentication.php',
    'database/migrations/2025_11_28_130000_fix_users_table_rls_for_pooler.php',
];

echo "=== Disabling RLS Migrations for MySQL ===\n\n";

$disabledDir = 'database/migrations_disabled';
if (!is_dir($disabledDir)) {
    mkdir($disabledDir, 0755, true);
    echo "✓ Created directory: $disabledDir\n\n";
}

$movedCount = 0;
$notFoundCount = 0;

foreach ($rlsMigrations as $migration) {
    if (file_exists($migration)) {
        $filename = basename($migration);
        $destination = $disabledDir . '/' . $filename;
        
        if (rename($migration, $destination)) {
            echo "✓ Disabled: $filename\n";
            $movedCount++;
        } else {
            echo "✗ Failed to move: $filename\n";
        }
    } else {
        $notFoundCount++;
    }
}

echo "\n=== Summary ===\n";
echo "Disabled: $movedCount migrations\n";
echo "Not found: $notFoundCount migrations\n\n";

echo "These migrations are now in: $disabledDir\n";
echo "They won't run with 'php artisan migrate'\n\n";

echo "Next steps:\n";
echo "1. Run: php artisan config:clear\n";
echo "2. Run: php artisan migrate:fresh\n";
echo "3. Your migrations will now work with MySQL!\n\n";

echo "Note: If you need to re-enable them later (for PostgreSQL),\n";
echo "just move them back from $disabledDir to database/migrations\n";
