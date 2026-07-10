# CI Workflow Standard

This document outlines the standard CI workflow architecture for any standalone Composer package in the Maatify ecosystem. It ensures a consistent, high-quality testing and static analysis baseline across all packages without coupling to any specific project.

## 1. Normative Language

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be interpreted as described in RFC 2119.

* **MUST / MUST NOT**: A mandatory baseline requirement.
* **SHOULD / SHOULD NOT**: The expected default; deviation requires an explicit documented reason.
* **MAY**: An optional capability or enhancement.

## 2. Canonical CI Architecture

Repositories MAY organize their workflows into multiple files (e.g., separating `quality`/`static analysis`, `tests`, `integration`, and `dependency compatibility`).
However, workflows MUST have:
* stable workflow names
* stable terminal gate job names
* clear ownership of each check
* no duplicate conflicting sources of truth

The standard does not mandate one exact number of workflow files.

## 3. Required-Check-Safe Path Scoping

A workflow or job used directly as a branch-protection required check **MUST always report a conclusion** for every protected pull request.
A directly required workflow MUST NOT disappear because of top-level path filtering.

### Allowed Models

* **Model A — Always run:** The required workflow runs on every protected pull request. This is valid for small or inexpensive package CI.
* **Model B — Always-triggered workflow with internal relevance detection:** The workflow always starts, then:
  1. a change-detection job determines which checks are relevant
  2. expensive jobs run conditionally
  3. a stable terminal gate job always runs
  4. the gate succeeds when no relevant work is required
  5. the gate fails when any relevant required job fails

Workflow-level `paths` filters MAY be used for workflows that are not configured as required branch-protection checks.
Repositories MUST NOT mark a top-level path-filtered workflow directly as a required check.
Branch protection MUST target stable aggregate/gate jobs, not unstable matrix child job names.

## 4. Path Relevance Rules

Every repository MUST derive relevant paths from its actual structure. Do not universally require paths that may not exist.

Examples of relevant paths:
* `src/**/*.php`
* `tests/**/*.php`
* `examples/**/*.php`
* `schema/**/*.sql`
* `tests/Fixtures/**/*.sql`
* `composer.json`
* `composer.lock`
* `phpstan.neon`
* `phpunit.xml` or `phpunit.xml.dist`
* `.php-cs-fixer.php`
* `.github/workflows/<workflow>.yml`

Rules:
* Include `composer.lock` ONLY when the repository tracks it.
* Never require a reusable library to add `composer.lock` solely for CI path filtering.
* Include the actual PHPUnit configuration filename used by the repository.
* Include package-owned schema and SQL fixture paths used by Integration tests.
* Include the workflow itself and all quality-tool configuration files that affect it.
* Documentation changes SHOULD trigger heavy checks only when the workflow validates documentation code blocks, generated files, or examples.
* Under the always-triggered gate model, irrelevant changes MAY skip heavy jobs, but the gate MUST still report success.

## 5. Composer Validation and Dependency Resolution

Composer validation MUST use:
```bash
composer validate --strict
```

Two dependency modes are defined:

### Repositories that track `composer.lock`
MUST use:
```bash
composer install --no-interaction --prefer-dist --no-progress
```
The lock file MUST NOT be rewritten during verification.

### Reusable libraries that do not track `composer.lock`
MUST use explicit dependency resolution, such as:
```bash
composer update --no-interaction --prefer-dist --no-progress
```
The CI process MAY generate a temporary local lock file, but it MUST NOT require committing it.

After dependency resolution, the pipeline MUST run:
```bash
composer check-platform-reqs
```
The flags `--ignore-platform-reqs` or `--ignore-platform-req` MUST NOT be used in required baseline CI.

## 6. Dependency Compatibility

Reusable libraries MUST validate both ends of their declared dependency constraints.

### Latest-compatible dependencies
A normal compatible dependency-resolution job MUST be executed.

### Lowest-supported dependencies
On the minimum supported PHP version, CI MUST run an appropriate resolution such as:
```bash
composer update --prefer-lowest --prefer-stable --no-interaction --prefer-dist --no-progress
```
Then run the relevant static and test checks.
The lowest-dependency job MUST NOT silently pass through `continue-on-error`, `allow-failure`, or `|| true`. If a package cannot support its declared lower bounds, the dependency constraint MUST be corrected.

## 7. PHP Version Compatibility

The declared Composer PHP constraint is a public compatibility contract.

* The minimum supported PHP minor MUST be tested.
* The latest stable PHP minor permitted by the Composer constraint MUST be tested.
* Every currently released PHP minor covered by the declared Composer constraint MUST either:
  * be represented directly in CI, or
  * have an explicit documented architectural exception.
* The minimum and latest supported versions MUST NOT be exempted.
* Future unreleased PHP versions implied by an open-ended constraint are not considered supported CI targets until released.
* Versions outside the declared Composer constraint MUST NOT be included merely to make a matrix appear broader.
* Packages SHOULD test every active intermediate minor directly; any omission requires documentation.

Rules:
* Static analysis and syntax compatibility SHOULD run on the minimum supported PHP version.
* Unit and Regression suites MUST represent the minimum and latest supported versions.
* Packages owning database/service behavior MUST run Integration coverage on the minimum and latest supported PHP versions unless the package supports only one PHP minor.
* Matrix `fail-fast` SHOULD be disabled so all compatibility failures are visible.

## 8. Mandatory Baseline Quality Checks

The standard MUST require, where applicable:

* **Composer validate --strict**
* **Dependency resolution/install**
* **Composer platform requirement validation**
* **PHP syntax validation**: Syntax-check every package-owned PHP path participating in runtime, tests, examples, or PHP-based tool configuration. MUST NOT silently ignore failures.
* **PHPStan level max**:
  * MUST enforce zero errors.
  * Runtime and tests MUST be included when tests are part of the configured PHPStan paths.
  * MUST NOT use a baseline.
  * MUST NOT use `ignoreErrors`.
  * MUST NOT use inline suppressions merely to make CI pass.
* **Code style**: When a supported formatter configuration (e.g., `.php-cs-fixer.php`) exists, CI MUST run a non-mutating check (e.g., `vendor/bin/php-cs-fixer fix --dry-run --diff`). CI MUST NEVER rewrite and commit formatting automatically during a required verification job.
* **Full test suite**: Where separate Unit, Regression, and Integration suites exist, run each explicitly. CI MUST also run the complete PHPUnit suite in at least one canonical environment.
* **Example syntax validation**
* **Composer security audit**
* **Workflow syntax/lint validation**

## 9. Composer Security Audit

CI MUST perform a non-interactive security audit after successful dependency resolution, for example:
```bash
composer audit --no-interaction
```

The repository MUST define an explicit policy for:
* security advisories
* abandoned packages
* known vulnerable direct dependencies
* known vulnerable transitive dependencies

Security audit failures MUST NOT be made non-blocking through `continue-on-error` or shell fallbacks.

## 10. Workflow Syntax Validation

Once a repository contains GitHub Actions workflows, CI MUST validate workflow syntax and semantics using a dedicated tool such as `actionlint` or an equivalent maintained validator.

* Workflow lint MUST be non-mutating.
* Invalid expressions, malformed YAML, invalid event structures, and invalid job dependencies MUST fail CI.
* The validator installation or action MUST be version-pinned.
* Workflow lint MUST include every file under `.github/workflows/`.

## 11. Integration-Test Rules

Packages that own persistence or external-service behavior MUST use the real supported service in Integration CI.

* SQLite substitution is FORBIDDEN for MySQL-owned behavior.
* In-memory or mocked substitutes are FORBIDDEN when they cannot prove the real storage contract.
* Service images MUST use explicit supported versions or immutable digests, never `latest`.
* Services MUST have health checks or deterministic readiness checks.
* CI credentials MUST be temporary, non-production, and local to the runner.
* Baseline CI MUST NOT require repository secrets.
* Integration tests MUST NOT access host application databases, schemas, services, or credentials.
* Integration tests MUST clean up package-created tables, triggers, temporary records, locks, and transactions where applicable.
* Tests that create or remove database objects MUST prove cleanup and repeatability.
* No transaction MAY leak between tests.
* No package-owned schema object MAY remain unexpectedly after the suite.
* If tests mutate schema-level objects or depend heavily on cleanup, CI MUST include either:
  * a repeated Integration run, or
  * an equivalent explicit repeatability and residue check.
* Service containers MUST represent only package-owned runtime dependencies.

## 12. Workflow Security and Supply-Chain Hardening

Workflows MUST require least privilege:
```yaml
permissions:
  contents: read
```
unless an additional permission is explicitly required and documented; only the minimum necessary permission may be granted.
`contents: read` is the normal verification baseline. Workflows may use a narrower permission set when possible. Any broader/additional permission requires explicit justification.

Rules:
* Baseline verification workflows MUST NOT receive write permissions.
* MUST NOT use `pull_request_target` to execute untrusted pull-request code.
* MUST NOT expose repository or environment secrets to untrusted fork code.
* Every externally sourced GitHub Action MUST be pinned to an immutable full commit SHA. This includes GitHub-owned actions such as actions under the `actions/*` organization.
* Every external reusable workflow MUST be pinned to an immutable full commit SHA.
* Human-readable comments MAY document the corresponding release/tag.
* Floating branches and tags such as `main`, `master`, `latest`, `v1`, `v2`, or other mutable references MUST NOT be used in required workflows.
* Local actions stored within the same repository are resolved from the checked-out repository commit and are not required to use an external SHA reference.
* Any downloaded CI binary or validator must use a version/checksum or another integrity-verifiable installation policy documented by the repository.
* Container/service images MUST use explicit versions, and digests SHOULD be used for high-assurance environments.
* MUST NOT upload credentials, database dumps, `.env` files, or sensitive logs as artifacts.

## 13. Execution Reliability

* Every required job MUST define an appropriate `timeout-minutes`.
* Every required workflow architecture MUST define an explicit concurrency and cancellation policy.
* `concurrency` MAY be configured at workflow level or job level according to the architecture.
* Job-level concurrency groups MUST be scoped so matrix siblings and independent required jobs do not cancel one another.
* Pull-request superseded runs SHOULD be cancelled.
* Default/protected-branch runs MUST NOT be cancelled in a way that hides the newest merged verification result.
* `cancel-in-progress` must be applied according to event/ref context, not blindly to every run.
* Required jobs must remain fail-closed.

Rules:
* Matrix jobs SHOULD use `fail-fast: false`.
* Required jobs MUST NOT use `continue-on-error`.
* Required commands MUST NOT use `|| true`.
* Shell scripts SHOULD use strict failure behavior such as `set -euo pipefail` where supported.
* Missing dependencies, services, privileges, or extensions MUST fail clearly.
* Tests MUST NOT be silently skipped because CI setup is incomplete.

## 14. Cache Policy

Caching is OPTIONAL. When enabled:
* Cache Composer download files, not the entire `vendor/` directory.
* Cache keys MUST include relevant inputs such as operating system, PHP version, Composer dependency files, and dependency mode.
* Stale cache MUST NOT determine correctness.
* A clean installation MUST remain possible.
* Cache restore failure MUST NOT conceal dependency installation failures.

Caching MUST NOT be part of the functional contract.

## 15. Coverage Policy

Coverage collection MAY be enabled. A coverage gate becomes mandatory only when the package has an explicitly approved threshold. Once adopted, reducing the threshold requires an explicit architectural decision and documented justification.
Generated coverage artifacts MUST NOT contain sensitive data.
Coverage MUST NOT replace behavior-focused Unit, Regression, or Integration tests.

## 16. Stable Gate Jobs and Branch Protection

Every workflow used by branch protection MUST expose a stable final gate job.
The final gate MUST:
* use `if: always()` or equivalent behavior where necessary
* inspect every required upstream job
* fail when a relevant upstream job fails or is cancelled unexpectedly
* succeed when non-relevant heavy jobs were intentionally skipped by the approved relevance detector
* have a stable name that does not contain variable matrix values
* be the check selected in branch protection

Repositories MUST NOT require individual matrix child jobs directly.
The gate MUST NOT hide `failure`, `cancelled`, `timed_out`, `action_required`, `startup_failure`, or equivalent unsuccessful states.

## 17. Trigger Events

Baseline verification MUST trigger on:
* `pull_request` targeting the protected/default branch
* `push` to the protected/default branch

`workflow_dispatch` MAY be added for manual verification.
Scheduled dependency-drift verification MAY be added for reusable libraries.

## 18. Generality and Package-Specific Configuration

This standard distinguishes between universal rules and repository-specific values.

* **Universal rules**: PHPStan max, real Integration services, minimum/latest PHP coverage, stable required gates, Composer validation, no hidden failures, least privilege.
* **Repository-specific values**: exact PHP versions, exact service versions, actual PHPUnit configuration filename, actual suite names, schema paths, environment variable names, service ports, package-owned trigger/table names, whether `composer.lock` is tracked.

Repository-specific values MUST be documented by each package, but MUST NOT be hardcoded into the universal standard.

## 19. Compliance Checklist

Any standalone Composer package in the Maatify ecosystem MUST verify the following before being considered compliant:

* [ ] Composer validation is strict
* [ ] dependency resolution strategy matches lock-file policy
* [ ] current compatible dependencies pass
* [ ] lowest supported dependencies pass
* [ ] platform requirements pass
* [ ] Composer audit passes
* [ ] PHP syntax passes
* [ ] PHPStan max passes with zero suppressions
* [ ] code-style dry-run passes when configured
* [ ] Unit suite passes where applicable
* [ ] Regression suite passes where applicable
* [ ] Integration suite uses real services where applicable
* [ ] full PHPUnit suite passes where PHPUnit/tests are applicable
* [ ] example PHP files pass syntax validation where examples exist
* [ ] minimum supported PHP is tested
* [ ] latest supported PHP is tested
* [ ] every currently released PHP minor covered by the declared constraint is tested or has an explicit documented architectural exception
* [ ] workflow files pass linting when workflows exist
* [ ] every externally sourced GitHub Action and external reusable workflow is pinned to an immutable full commit SHA
* [ ] downloaded CI tools and validators use a documented integrity-verifiable installation policy where downloaded tools exist
* [ ] permissions follow least privilege
* [ ] no baseline secrets or Host dependencies exist
* [ ] required gates always report
* [ ] branch protection requires stable gates only
* [ ] no continue-on-error or hidden failures exist
* [ ] package-created service/database state is cleaned up where Integration tests create such state
