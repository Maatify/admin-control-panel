# Contributing to maatify/image-profile

Thank you for your interest in contributing. This document explains how to set
up your environment, run tests and static analysis, and follow the project's
coding standards before opening a pull request.

---

## Requirements

| Tool | Minimum version |
|------|----------------|
| PHP | 8.1 |
| Composer | 2.x |
| ext-gd | bundled with PHP |
| ext-pdo | bundled with PHP |
| ext-pdo_sqlite | for integration tests |
| ext-fileinfo | bundled with PHP |

---

## Local setup

```bash
git clone https://github.com/Maatify/image-profile.git
cd image-profile
composer install
```

---

## Running the test suite

```bash
# All suites
vendor/bin/phpunit

# Individual suites
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Contract

# With coverage (requires Xdebug or pcov)
vendor/bin/phpunit --coverage-html build/coverage/html
```

The integration suite uses **SQLite in-memory** — no external database is
required. The unit and contract suites use GD to generate test images in `/tmp`;
they clean up after themselves via `TestImageFactory::cleanup()`.

---

## Static analysis

```bash
vendor/bin/phpstan analyse
```

This runs at level 10 (max) with bleedingEdge rules. All five source trees are
analysed: `src/`, `Application/`, `Infrastructure/`, `Adapter/`, `Storage/`.

Zero errors are required before a PR can merge.

---

## Coding standards

- `declare(strict_types=1)` on **every** PHP file — no exceptions.
- All classes in `src/` must be `final` (entities and DTOs also `readonly`).
- No `array` returned from any public method that represents a collection of
  domain objects — use typed `*CollectionDTO` or `*Collection` value objects.
- No framework imports (`Slim\`, `Symfony\`, `Laravel\`, `Psr\Http\Message\`)
  inside `src/`. Adapters live in `Adapter/`.
- No cloud SDK imports (`Aws\`) inside `src/`. Storage adapters live in
  `Storage/`.
- `with()` methods on collection types must return **new** instances — never
  mutate.
- `ValidationErrorCodeEnum` string values are **immutable once released**.
  Renaming a case or changing its string value is a breaking change that
  requires a major version bump.

---

## Architectural rules (summary)

| Rule | Detail |
|------|--------|
| Read / write split | `ImageProfileProviderInterface` is read-only. `ImageProfileRepositoryInterface` is write-only. Never mix. |
| Validator never throws on business failures | `validateByCode()` always returns `ImageValidationResultDTO`. Only infrastructure failures may throw. |
| Short-circuit only on infra failures | Profile not found, file not readable, metadata unreadable → short-circuit. All rule errors (mime, ext, dimensions, size) → collected exhaustively. |
| `findByCode` never filters `is_active` | The validator owns that check. Providers return the row regardless. |
| PDO mutations always re-fetch | After every INSERT / UPDATE the row is re-read so the returned entity reflects actual DB state. |
| PDOException always wrapped | Never let a raw `PDOException` escape a repository or provider. Wrap as `ImageProfileException`. |

---

## Branch strategy

| Branch | Purpose |
|--------|---------|
| `main` | Stable, released code only |
| `develop` | Integration branch for upcoming release |
| `feature/*` | Individual feature work |
| `fix/*` | Bug fixes |

Open PRs against **`develop`**, not `main`.

---

## Commit messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>(<scope>): <short summary>

[optional body]
[optional footer]
```

Common types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`.

Examples:

```
feat(validator): collect all rule errors before returning result
fix(provider): wrap PDOException in ImageProfileException
test(contract): add stability test for ValidationErrorCodeEnum
docs(readme): document full upload flow with DO Spaces
```

---

## Pull request checklist

Before marking a PR ready for review:

- [ ] All three test suites pass (`Unit`, `Integration`, `Contract`)
- [ ] PHPStan reports zero errors
- [ ] `declare(strict_types=1)` present on every new file
- [ ] No `array` returned from a public method that represents a collection
- [ ] No framework imports inside `src/`
- [ ] `ValidationErrorCodeEnum` string values unchanged (or major version bumped)
- [ ] New behaviour is covered by at least one test
- [ ] `CHANGELOG.md` updated under `[Unreleased]`
- [ ] `EXTRACTION_CHECKLIST.md` reviewed if structural changes were made

---

## Reporting issues

Open a GitHub issue and include:

- PHP version (`php -v`)
- Composer version (`composer -V`)
- A minimal reproducible example
- The full error message or unexpected output

---

## License

By contributing you agree that your changes will be released under the
[MIT License](LICENSE) that covers this project.
