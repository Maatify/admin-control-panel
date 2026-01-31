<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Maatify\AdminKernel\Bootstrap\Container;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\EmailDelivery\Renderer\EmailRendererInterface;
use Maatify\EmailDelivery\Transport\EmailTransportInterface;
use Maatify\EmailDelivery\Worker\EmailQueueWorker;

/*
|--------------------------------------------------------------------------
| 1️⃣ Load ENV (HOST responsibility)
|--------------------------------------------------------------------------
*/
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
} catch (Throwable $e) {
    fwrite(STDERR, "ENV Load Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 2️⃣ Build Runtime Config DTO
|--------------------------------------------------------------------------
*/
try {
    $runtimeConfig = AdminRuntimeConfigDTO::fromArray($_ENV);
} catch (Throwable $e) {
    fwrite(STDERR, "Runtime Config Invalid: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 3️⃣ Bootstrap Container
|--------------------------------------------------------------------------
*/
try {
    $container = Container::create($runtimeConfig);
} catch (Throwable $e) {
    fwrite(STDERR, "Container Bootstrap Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 4️⃣ Resolve Dependencies
|--------------------------------------------------------------------------
*/
try {
    /** @var PDO $pdo */
    $pdo = $container->get(PDO::class);

    /** @var CryptoProvider $crypto */
    $crypto = $container->get(CryptoProvider::class);

    /** @var EmailRendererInterface $renderer */
    $renderer = $container->get(EmailRendererInterface::class);

    /** @var EmailTransportInterface $transport */
    $transport = $container->get(EmailTransportInterface::class);

    /** @var CryptoContextProviderInterface $cryptoContextProvider */
    $cryptoContextProvider = $container->get(CryptoContextProviderInterface::class);

} catch (Throwable $e) {
    fwrite(STDERR, "Dependency Resolution Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 5️⃣ Run Worker
|--------------------------------------------------------------------------
*/
try {
    $worker = new EmailQueueWorker(
        $pdo,
        $crypto,
        $renderer,
        $transport,
        $cryptoContextProvider
    );

    // Process up to 50 queued emails
    $worker->processBatch(50);

} catch (Throwable $e) {
    fwrite(STDERR, "Worker Execution Failed: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}
