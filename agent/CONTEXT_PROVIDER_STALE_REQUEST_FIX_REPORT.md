# Context Provider Stale Request Fix Report

## Executive Summary
Fixed a critical issue where `HttpContextProvider` was resolving a stale `ServerRequestInterface` instance (missing `admin_id`) from the DI container. This was caused by `SessionGuardMiddleware` modifying the request object but not updating the container's binding.

## Root Cause
1.  `ContextProviderMiddleware` injects the initial `ServerRequestInterface` into the container.
2.  `SessionGuardMiddleware` runs later, validates the session, and adds the `admin_id` attribute to the request using `$request->withAttribute(...)`.
3.  Since `ServerRequestInterface` is immutable, a new instance is created.
4.  The container retained the *original* request instance.
5.  `HttpContextProvider` (resolved via DI) received the original request (without `admin_id`), causing `admin()` to return `null`.

## Exact Fix
Modified `app/Http/Middleware/SessionGuardMiddleware.php`:
-   Injected `DI\Container` into the constructor.
-   Updated the container's `ServerRequestInterface` binding with the authenticated request instance immediately after successful session validation.

```php
// app/Http/Middleware/SessionGuardMiddleware.php

// Injected Container
public function __construct(
    SessionValidationService $sessionValidationService,
    private Container $container
) {
    $this->sessionValidationService = $sessionValidationService;
}

public function process(...) {
    // ...
    $adminId = $this->sessionValidationService->validate($token);
    $request = $request->withAttribute('admin_id', $adminId);

    // FIX: Update container with the authenticated request
    $this->container->set(ServerRequestInterface::class, $request);

    return $handler->handle($request);
}
```

## Verification Checklist
-   [x] **Reproduction Script**: Created `reproduce_issue.php` which simulated the middleware pipeline and DI container interaction.
    -   Before fix: Script failed (Container held stale request).
    -   After fix: Script passed (Container held updated request with `admin_id`).
-   [x] **Code Inspection**: Verified `app/Context/HttpContextProvider.php`.
    -   `admin()` method catches `Throwable` and returns `null`, ensuring safety if `admin_id` is missing or resolver fails.
    -   `request()` relies on attributes guaranteed by `RequestIdMiddleware`.
-   [x] **Existing Tests**: Ran `vendor/bin/phpunit`. Failures observed were due to missing environment configuration (database credentials) and unrelated to the changes.

## Risks / Non-Goals
-   **Risk**: If `SessionGuardMiddleware` is manually instantiated (e.g. `new SessionGuardMiddleware(...)`) without the container argument, it will fail. Verified `routes/web.php` adds it via class name (`SessionGuardMiddleware::class`), allowing DI to handle instantiation.
-   **Non-Goal**: Did not refactor `HttpContextProvider` as it was already compliant with safety requirements.
