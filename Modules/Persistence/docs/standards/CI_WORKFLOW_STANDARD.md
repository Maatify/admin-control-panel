# CI Workflow Standard

This document outlines the standard CI workflow architecture for any standalone Composer package in the Maatify ecosystem. It ensures a consistent, high-quality testing and static analysis baseline across all packages without coupling to any specific project.

## 1. Baseline Requirements

Any new package must implement GitHub Actions CI pipelines that include at least the following checks:

* **Composer validation**
* **Composer install**
* **PHP syntax checks**
* **PHPStan level max**
* **PHPUnit tests** (where applicable)
* **Example syntax checks** (where examples exist)
* **Integration tests** (where the package owns persistence or database behavior)

## 2. Proposed Workflow Structure

We recommend separating static analysis and testing into two distinct workflows:

* `.github/workflows/phpstan.yml`
* `.github/workflows/phpunit.yml`

## 3. Rules for `phpstan.yml`

* Must trigger on `push` and `pull_request`.
* Must use `on.push.paths` and `on.pull_request.paths`.
* Must only run when changes affect files relevant to static analysis.
* **Recommended path filters:**
  * `src/**/*.php`
  * `tests/**/*.php` (if tests are included in PHPStan analysis or syntax checks)
  * `examples/**/*.php` (if examples are syntax-checked by this workflow)
  * `composer.json`
  * `composer.lock`
  * `phpstan.neon`
  * `.github/workflows/phpstan.yml`
* **Must run the following steps:**
  * `composer validate`
  * `composer install --no-interaction --prefer-dist --no-progress`
  * `php -l` over relevant PHP paths
  * `vendor/bin/phpstan analyse -c phpstan.neon`
* PHPStan level **must be max**.

## 4. Rules for `phpunit.yml`

* Must trigger on `push` and `pull_request`.
* Must use `on.push.paths` and `on.pull_request.paths`.
* Must only run when changes affect files relevant to tests.
* **Recommended path filters:**
  * `src/**/*.php`
  * `tests/**/*.php`
  * `examples/**/*.php` (if examples are syntax-checked by this workflow)
  * Package-owned SQL/schema paths (if integration tests depend on them). Actual paths depend on package organization, e.g.:
    * `schema/**/*.sql`
    * `src/**/Database/**/*.sql`
    * `src/**/*.sql`
  * `composer.json`
  * `composer.lock`
  * `phpunit.xml.dist`
  * `.github/workflows/phpunit.yml`
* **Execution Requirements:**
  * Must run unit tests if a Unit suite exists.
  * Must run regression tests if a Regression suite exists.
  * Must syntax-check examples if `examples/` directory exists.
  * Must not require host application code or framework bindings.
  * Must not require secrets for baseline CI.

## 5. Integration Test Rules

* **Required** when the package owns persistence or database behavior.
* Must use real service dependency (e.g., MySQL 8.0 for MySQL-owned packages).
* SQLite fallback/support is strictly forbidden unless a future package explicitly owns SQLite as a real supported target.
* Integration DSN/env names may be package-specific, but the pattern must be documented.
* Integration tests must not depend on host project databases or host schema.
* Service containers must only represent package-owned runtime dependencies, not host applications.

## 6. Path-Scoped Workflow Triggers

CI workflows should not run on every repository change by default.

* Each workflow must use path filters so it runs only when relevant files change.
* Documentation-only changes should not trigger PHPUnit/PHPStan unless the package explicitly validates documentation snippets or examples in that workflow.
* Workflow files must include themselves in their own path filters.
* Composer files (`composer.json`, `composer.lock`) must be included because dependency changes affect both static analysis and tests.
* Test workflows must include schema files when database integration tests depend on package-owned SQL schema.

## 7. Required-Check Warning

> **Warning:** If GitHub branch protection marks a workflow as a required check, path-filtered workflows may be skipped when no matching files change.

The CI standard requires maintainers to ensure branch protection is compatible with path-filtered workflows, either by:
* requiring only always-running aggregate/gate checks, or
* configuring required checks so skipped path-filtered workflows do not leave pull requests blocked.

*(Note: Do not implement such a gate automatically; this is a policy rule that must be handled manually per repository.)*

## 8. Required vs Optional Checks Summary

### Required for all packages:
* `composer validate`
* `composer install`
* PHP syntax check
* PHPStan max

### Required where applicable:
* PHPUnit Unit suite
* PHPUnit Regression suite
* PHPUnit Integration suite
* examples syntax checks
* service containers such as MySQL
