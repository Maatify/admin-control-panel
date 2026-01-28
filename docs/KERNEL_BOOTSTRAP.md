# Kernel Bootstrap & Entry Boundary

## Overview

The `maatify/admin-control-panel` project is structured as a **Kernel** that can be:
1.  **Run Standalone**: Using the provided `public/index.php`.
2.  **Embedded in a Host Application**: By mounting the Kernel logic via a custom entry point.

To achieve this, the entry logic has been encapsulated in `App\Kernel\AdminKernel`.

## The `AdminKernel`

The `App\Kernel\AdminKernel` class is the single entry point for booting the application. It handles:
*   Container initialization (via `App\Bootstrap\Container`).
*   Slim App creation.
*   Registration of global middlewares (BodyParsing, ErrorMiddleware).
*   Registration of Error Handlers (Validation, HTTP Errors, etc.).
*   Route loading (via `routes/web.php`).

### Usage

```php
use App\Kernel\AdminKernel;

// Boot the kernel and run the app
AdminKernel::boot()->run();
```

### Hooking into the Container

The `boot` method accepts an optional callable to hook into the Container Builder before the container is compiled. This allows host applications to override or extend services.

```php
AdminKernel::boot(function (ContainerBuilder $builder) {
    $builder->addDefinitions([
        // Override definitions here
    ]);
})->run();
```

## `public/index.php`

The `public/index.php` file is now a thin wrapper that delegates entirely to the Kernel. It is owned by the host application (or the standalone deployment) and can be modified to suit environment-specific needs, as long as it eventually boots the Kernel.

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\App\Kernel\AdminKernel::boot()->run();
```

## Routing

The kernel automatically loads routes from `routes/web.php`. This file returns a closure that registers routes and app-specific middleware (like Authentication and Telemetry) onto the Slim App instance.

Host applications mounting the kernel should be aware that `AdminKernel::boot()` will register these routes at the root level.
