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

use App\Bootstrap\Container;

// Use Container to handle ENV and Config
// This ensures strict adherence to AdminConfigDTO logic
try {
    $container = Container::create();
    $pdo = $container->get(PDO::class);

    echo "Connected to database via Container/AdminConfigDTO.\n";

    $sql = file_get_contents(__DIR__ . '/../database/schema_verification.sql');

    if ($sql === false) {
        echo "Error reading schema file.\n";
        exit(1);
    }

    $pdo->exec($sql);
    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration/Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
