<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Container;
use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Email\Config\EmailTransportConfigDTO;
use App\Modules\Email\Renderer\EmailRendererInterface;
use App\Modules\Email\Transport\SmtpEmailTransport;
use App\Modules\Email\Worker\EmailQueueWorker;

// 1. Bootstrap Container
try {
    $container = Container::create();
} catch (Throwable $e) {
    fwrite(STDERR, "Worker Bootstrap Failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// 2. Retrieve Dependencies
try {
    /** @var PDO $pdo */
    $pdo = $container->get(PDO::class);

    /** @var CryptoProvider $crypto */
    $crypto = $container->get(CryptoProvider::class);

    /** @var EmailRendererInterface $renderer */
    $renderer = $container->get(EmailRendererInterface::class);

    /** @var EmailTransportConfigDTO $emailConfig */
    $emailConfig = $container->get(EmailTransportConfigDTO::class);

} catch (Throwable $e) {
    fwrite(STDERR, "Dependency Resolution Failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// 3. Instantiate Transport & Worker
try {
    $transport = new SmtpEmailTransport($emailConfig);
    $worker = new EmailQueueWorker($pdo, $crypto, $renderer, $transport);

    // 4. Run Batch
    $worker->processBatch(50);

} catch (Throwable $e) {
    fwrite(STDERR, "Worker Execution Failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
