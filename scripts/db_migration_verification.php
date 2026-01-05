<?php

// Check if we are in the sandbox environment where vendor is in /tmp
if (file_exists('/tmp/vendor/autoload.php')) {
    require '/tmp/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    echo "Vendor autoload not found.\n";
    exit(1);
}

use App\Infrastructure\Database\PDOFactory;
use Dotenv\Dotenv;

// Only load .env if it exists, otherwise rely on environment
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Check if we have ENV vars, else default to values that might work or fail
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? '127.0.0.1';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'test';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

echo "Connecting to $host...\n";

$factory = new PDOFactory($host, $dbName, $user, $pass);
$pdo = $factory->create();

$sql = file_get_contents(__DIR__ . '/../database/schema_verification.sql');

if ($sql === false) {
    echo "Error reading schema file.\n";
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
