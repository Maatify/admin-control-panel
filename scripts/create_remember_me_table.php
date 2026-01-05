<?php

require_once __DIR__ . '/../app/Bootstrap/Container.php';

use App\Infrastructure\Database\PDOFactory;

$container = require __DIR__ . '/../app/Bootstrap/Container.php';
$pdo = $container->get(PDOFactory::class)->createPDO();

$sql = "
CREATE TABLE IF NOT EXISTS admin_remember_me_tokens (
    selector CHAR(32) NOT NULL PRIMARY KEY,
    hashed_validator CHAR(64) NOT NULL,
    admin_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_agent_hash CHAR(64) NOT NULL,
    CONSTRAINT fk_armt_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$pdo->exec($sql);
echo "Table created successfully.\n";
