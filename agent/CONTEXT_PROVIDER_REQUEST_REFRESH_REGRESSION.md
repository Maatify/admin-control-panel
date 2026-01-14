# Context Provider Request Refresh Regression Report

## What was tested
A new regression test `tests/Integration/Context/HttpContextProviderRegressionTest.php` was added.
This test simulates the middleware pipeline involving `ContextProviderMiddleware` and `SessionGuardMiddleware` (with a mock `SessionValidationService`).
It specifically tests that `HttpContextProvider`, when resolved from the DI container *after* the middleware pipeline runs, has access to the updated `ServerRequestInterface` containing the `admin_id` attribute.

## Why this bug existed
Previously, `SessionGuardMiddleware` would validate the session and create a *new* request instance with the `admin_id` attribute (since PSR-7 requests are immutable). However, it did not update the `ServerRequestInterface` binding in the DI container.
`HttpContextProvider` is registered to inject `ServerRequestInterface`. Since it was resolving the *original* request instance (bound by `ContextProviderMiddleware` earlier in the lifecycle), it never saw the `admin_id` attribute, causing `admin()` to return `null` even for authenticated sessions.

## Why it cannot regress anymore
The regression test ensures that the DI container holds the *updated* request object after `SessionGuardMiddleware` executes.
If `SessionGuardMiddleware` stops updating the container (e.g., if the line `$this->container->set(...)` is removed), `HttpContextProvider` will receive the old request (missing `admin_id`), and the assertion `$this->assertNotNull($provider->admin())` will fail.
This guarantees that any future changes to `SessionGuardMiddleware` must preserve this container update behavior to pass the test suite.

## Test result
**PASS**
- The test was verified to pass with the current fix.
- The test was verified to FAIL when the fix was temporarily reverted.
