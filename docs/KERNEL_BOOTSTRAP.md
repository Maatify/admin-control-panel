# Kernel Bootstrap & Entry Boundary

## Overview

The `maatify/admin-control-panel` project is structured as a **Kernel** that can be embedded in a host application.

The Kernel defines wiring only. **All HTTP bootstrap policies belong to the host application.**

## The `AdminKernel`

The `App\Kernel\AdminKernel` class is a **thin façade** used to boot the application.
It has strictly limited responsibilities:

1.  Initialize the Container (via `App\Bootstrap\Container`).
2.  Create the Slim App instance.
3.  Delegate HTTP bootstrap to the host-provided logic (e.g. `app/Bootstrap/http.php`).

The Kernel does **NOT**:
*   Configure middleware.
*   Define error handling strategies.
*   Set up routing policies.
*   Enforce runtime behavior.

### Usage

```php
use App\Kernel\AdminKernel;

// Boot the kernel and run the app
AdminKernel::boot()->run();
```

## Bootstrap Delegation

The actual HTTP stack configuration (Middleware, Error Handlers, Routes) is delegated to `app/Bootstrap/http.php`.
This file is owned by the host application environment.

When `AdminKernel::boot()` is called, it:
1.  Creates the App.
2.  Immediately requires and invokes `app/Bootstrap/http.php` with the App instance.

Host applications mounting the Kernel can customize this behavior by providing their own bootstrap logic if necessary, or by modifying `app/Bootstrap/http.php` directly in their deployment.

## `public/index.php`

The `public/index.php` file is a thin wrapper that delegates entirely to the Kernel.

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\App\Kernel\AdminKernel::boot()->run();
```
