#!/usr/bin/env php
<?php
/**
 * Database initialization CLI tool
 * Usage: php database/init.php [--seed]
 */

$projectRoot = dirname(__DIR__);
$config = require $projectRoot . '/config/config.php';

echo "=== ssjmukapi Database Initialization ===";

// Parse arguments
$shouldSeed = in_array('--seed', $argv);

// Connect to MySQL server (without database)
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        $config['db']['host'],
        $config['db']['port']
```php
#!/usr/bin/env php
<?php
/**
 * Database initialization CLI tool
 * Usage: php database/init.php
 */

$projectRoot = dirname(__DIR__);
$config = require $projectRoot . '/config/config.php';

echo "=== ssjmukapi Database Initialization ===";

// Connect to MySQL server (without database)
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        $config['db']['host'],
        $config['db']['port']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✓ Connected to MySQL server\n";
} catch (PDOException $e) {
    echo "✗ Failed to connect to MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// Create database if not exists
try {
    $dbName = $config['db']['name'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$dbName}' ready\n";
} catch (PDOException $e) {
    echo "✗ Failed to create database: " . $e->getMessage() . "\n";
    exit(1);
}

// Switch to the database
$pdo->exec("USE `{$dbName}`");

// Run consolidated SQL file
echo "\n--- Running database setup ---\n";
$sqlFile = $projectRoot . '/database/ssjmukapi.sql';
if (!is_file($sqlFile)) {
    echo "✗ SQL file not found: {$sqlFile}\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
try {
    $pdo->exec($sql);
    echo "✓ Database setup completed successfully (Schema + Seed data)\n";
} catch (PDOException $e) {
    echo "✗ Setup error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Database initialization complete ===\n";
echo "Next steps:\n";
echo "  1. Start the API: cd public && php -S localhost:8081\n";
echo "  2. Test endpoint: curl http://localhost:8081/api/v1/health\n";
echo "  3. View docs: http://localhost:8081/docs/\n";
```
