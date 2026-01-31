<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maatify\AdminKernel\Kernel\AdminKernel;
use Maatify\AdminKernel\Kernel\KernelOptions;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Dotenv\Dotenv;

// 1️⃣ Load ENV (HOST responsibility)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 2️⃣ Build Runtime Config DTO
$runtimeConfig = AdminRuntimeConfigDTO::fromArray($_ENV);

// 3️⃣ Kernel options
$options = new KernelOptions();
$options->runtimeConfig = $runtimeConfig;

// (اختياري)
// $options->registerInfrastructureMiddleware = true;
// $options->strictInfrastructure = true;
// $options->routes = fn ($app) => ...;

// 4️⃣ Boot & Run
AdminKernel::bootWithOptions($options)->run();
