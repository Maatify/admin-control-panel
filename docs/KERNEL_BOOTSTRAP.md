# Kernel Bootstrap & Entry Boundary

## Overview

The `maatify/admin-control-panel` project is structured as a **Kernel** that can be embedded in a host application.

The Kernel defines wiring only. **All HTTP bootstrap policies belong to the host application.**

## The `AdminKernel`

The `Maatify\AdminKernel\Kernel\AdminKernel` class is a **thin façade** used to boot the application.
It has strictly limited responsibilities:

1.  Initialize the Container (via `Maatify\AdminKernel\Bootstrap\Container`).
2.  Create the Slim App instance.
3.  Delegate HTTP bootstrap to the host-provided logic (e.g. `app/Modules/AdminKernel/Bootstrap/http.php`).

The Kernel does **NOT**:
*   Configure middleware.
*   Define error handling strategies.
*   Set up routing policies.
*   Enforce runtime behavior.

### Usage

```php
use Maatify\AdminKernel\Kernel\AdminKernel;

// Boot the kernel and run the app
AdminKernel::boot()->run();
```

## Bootstrap Delegation

The actual HTTP stack configuration (Middleware, Error Handlers, Routes) is delegated to the http bootstrap file.
This file is owned by the host application environment (or defaults to the kernel's if not overridden).

When `AdminKernel::boot()` is called, it:
1.  Creates the App.
2.  Immediately requires and invokes the http bootstrap logic with the App instance.

Host applications mounting the Kernel can customize this behavior by providing their own bootstrap logic via `KernelOptions`, or by modifying the bootstrap file directly in their deployment.

## `public/index.php`

The `public/index.php` file is a thin wrapper that delegates entirely to the Kernel.

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\Maatify\AdminKernel\Kernel\AdminKernel::boot()->run();
```
