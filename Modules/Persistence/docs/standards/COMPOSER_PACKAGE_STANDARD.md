# Maatify Composer Package Standard

**Maatify Standalone PHP Composer Library Standard — v1**

This document defines the canonical `composer.json` contract for standalone, reusable PHP libraries in the Maatify ecosystem.

It MUST be read together with:

- [PACKAGE_BUILDING_STANDARD.md](PACKAGE_BUILDING_STANDARD.md) for runtime architecture and package structure.
- [CI_WORKFLOW_STANDARD.md](CI_WORKFLOW_STANDARD.md) for automated Composer verification.
- [LIBRARY_PRESENTATION_STANDARD.md](LIBRARY_PRESENTATION_STANDARD.md) for README, badges, governance identity, and presentation-facing consistency.

---

## 1. Normative Language

The key words **MUST**, **MUST NOT**, **REQUIRED**, **SHOULD**, **SHOULD NOT**, **MAY**, and **OPTIONAL** in this document are to be interpreted as described in RFC 2119.

- **MUST / MUST NOT / REQUIRED**: Binding rules.
- **SHOULD / SHOULD NOT**: The default expected behavior. A deviation requires a documented technical reason.
- **MAY / OPTIONAL**: A permitted choice that depends on the library's actual requirements.

A rule in this Standard does not become optional merely because Composer itself permits another shape. This document defines the stricter Maatify library profile.

---

## 2. Scope and Ownership

This Standard governs the construction and review of `composer.json` for Maatify standalone PHP libraries.

It owns rules for:

- Package identity.
- Package metadata.
- Top-level field ordering.
- PHP and extension requirements.
- Runtime and development dependencies.
- Dependency constraints.
- Production and development autoloading.
- Composer scripts.
- Composer configuration.
- Stability policy.
- Optional package-link fields.
- Custom repository restrictions.
- Package archive safety.
- Reusable-library lock-file policy.
- Composer validation and review.

It does **not** govern:

- Runtime architecture.
- DTO, repository, command, or service design.
- Database schema or SQL behavior.
- Exception hierarchy.
- Test architecture.
- GitHub Actions implementation.
- README visual layout.
- Badges.
- Governance-document formatting.
- Release publication.

### 2.1 Relationship to Other Standards

- `PACKAGE_BUILDING_STANDARD.md` governs package boundaries, namespaces, source structure, runtime design, schema, exceptions, DTOs, repositories, and tests.
- `COMPOSER_PACKAGE_STANDARD.md` governs `composer.json` as the package metadata, dependency, autoload, scripts, configuration, and distribution contract.
- `CI_WORKFLOW_STANDARD.md` governs how Composer contracts are verified through strict validation, dependency resolution, platform checks, audit, and quality gates.
- `LIBRARY_PRESENTATION_STANDARD.md` governs public presentation and consistency between Composer metadata, README, Packagist, and GitHub.

No Standard SHOULD duplicate the detailed rules owned by another Standard. Cross-references MUST be used instead.

---

## 3. Applicability

This version applies exclusively to:

> **Maatify standalone reusable PHP Composer libraries**

It does not automatically apply to:

- Applications.
- Framework host projects.
- Composer plugins.
- Metapackages.
- Project templates.
- PHP extensions written in C.
- JavaScript, Rust, or other language ecosystems.

Those package types require a separate profile or Standard.

### 3.1 Root-Package Context

Some Composer fields are root-only, including `require-dev`, `autoload-dev`, `repositories`, `config`, `scripts`, `minimum-stability`, and `prefer-stable`.

Rules for those fields govern the library repository while it is being developed, tested, or released as the root package. They MUST NOT be presented as runtime behavior imposed on consumers when the library is installed as a dependency.

---

## 4. Core Composer Contract Principles

1. `composer.json` is a public package contract, not an internal installation note.
2. Every directly used runtime dependency MUST be declared directly.
3. A package MUST NOT rely on a transitive dependency as though it were direct.
4. Development tools MUST NOT be placed in `require`.
5. Runtime dependencies MUST NOT be placed only in `require-dev`.
6. Metadata MUST be accurate, current, and non-misleading.
7. The PHP constraint is a public compatibility promise.
8. The production autoload mapping is part of the public runtime contract.
9. Composer scripts MUST map to real, maintained commands.
10. Published versions MUST come from VCS tags; the `version` field MUST NOT be committed.
11. Reusable Maatify libraries MUST NOT commit `composer.lock`.
12. A release-ready package MUST pass `composer validate --strict`.
13. Package metadata MUST match the repository, runtime, documentation, and CI claims.
14. Composer configuration MUST NOT hide unsupported platform assumptions.
15. Installing the library as a dependency MUST NOT require consumer-side mutations or manual setup scripts.
16. Empty, unused, speculative, or copied fields MUST be removed.
17. `composer.json` MUST remain valid JSON without comments or trailing commas.

---

## 5. Canonical Placeholders

Templates in this Standard use the following placeholders:

- `{PACKAGE_SLUG}`: The Composer package name segment after `maatify/`.
- `{REPOSITORY_SLUG}`: The repository name inside the Maatify organization.
- `{PACKAGE_DISPLAY_NAME}`: The human-readable library name.
- `{PACKAGE_DESCRIPTION}`: A concise and technically accurate package description.
- `{ROOT_NAMESPACE}`: The PSR-4 root namespace without the final separator.
- `{TEST_NAMESPACE}`: The test namespace root without the final separator; normally `{ROOT_NAMESPACE}\Tests`.
- `{MINIMUM_PHP_VERSION}`: The minimum supported PHP minor, such as `8.2`.
- `{MINIMUM_PHP_PATCH_VERSION}`: The root development baseline, such as `8.2.0`.
- `{LICENSE_SPDX}`: The approved SPDX license identifier.
- `{PRIMARY_DOMAIN_KEYWORD}`: The main searchable domain term for the library.
- `{RUNTIME_EXTENSION_NAME}`: A directly required PHP extension name without the `ext-` prefix.
- `{RUNTIME_PACKAGE_NAME}`: A direct runtime package in `vendor/package` form.
- `{RUNTIME_PACKAGE_CONSTRAINT}`: The approved stable constraint for a runtime package.
- `{PHPUNIT_CONSTRAINT}`: The approved PHPUnit constraint compatible with the supported PHP range.
- `{PHPSTAN_CONSTRAINT}`: The approved PHPStan constraint.
- `{CS_FIXER_CONSTRAINT}`: The approved PHP CS Fixer constraint.
- `{README_FILE}`: A non-default README path when the package intentionally does not use `README.md`.
- `{SECURITY_POLICY_URL}`: A stable absolute URL to the package security policy when supplied in Composer support metadata.

Every placeholder used by a template MUST be defined in this section. Every placeholder MUST be replaced before a real `composer.json` is committed.

---

## 6. Canonical Top-Level Field Order

When fields are present, the canonical order is:

1. `name`
2. `description`
3. `keywords`
4. `homepage`
5. `readme`
6. `type`
7. `license`
8. `authors`
9. `support`
10. `funding`
11. `autoload`
12. `autoload-dev`
13. `require`
14. `require-dev`
15. `conflict`
16. `provide`
17. `replace`
18. `suggest`
19. `bin`
20. `scripts`
21. `config`
22. `extra`
23. `archive`
24. `minimum-stability`
25. `prefer-stable`
26. `abandoned`

Rules:

- An unused field MUST be omitted.
- Empty arrays and empty objects MUST NOT be retained merely to preserve the order.
- `require`, `require-dev`, and other package maps MUST be alphabetically sorted.
- `config.sort-packages` MUST be enabled so future package additions remain sorted.
- Optional fields MUST be placed in their canonical position.
- Field ordering is a readability and review rule; it does not change Composer semantics.

---

## 7. Package Identity

### 7.1 Package Name

The canonical form is:

```json
"name": "maatify/{PACKAGE_SLUG}"
```

Rules:

- The vendor MUST be `maatify`.
- The complete name MUST be lowercase.
- The format MUST be `vendor/package`.
- Multi-word slugs MUST use `kebab-case`.
- Spaces and uppercase characters are forbidden.
- Underscores SHOULD NOT be used even though Composer may accept them.
- The slug MUST reflect the library's actual responsibility.
- The Composer package slug SHOULD match `{REPOSITORY_SLUG}`.
- A mismatch requires a documented distribution reason.
- Renaming a published package is a compatibility and distribution change, not presentation polish.

### 7.2 Package Display Name

`{PACKAGE_DISPLAY_NAME}` is used in human-facing documentation, not as a replacement for the canonical Composer name.

The display name MAY use title case and spaces. It MUST NOT change the Composer identity.

---

## 8. Description

The canonical field is:

```json
"description": "{PACKAGE_DESCRIPTION}"
```

The description:

- MUST be one concise sentence or sentence fragment.
- MUST describe the library's primary, currently implemented purpose.
- MUST mention a core technology only when it materially defines the package.
- MUST NOT claim the package is standalone, framework-agnostic, secure, audited, or production-ready unless those claims are proven.
- MUST NOT include a version number.
- MUST NOT contain temporary wording such as `coming soon`, `work in progress`, `unreleased`, or `experimental` in a stable release.
- MUST NOT use exaggerated marketing language.
- SHOULD be semantically consistent with the GitHub repository description.
- MAY differ from the GitHub description in length or wording.
- MUST remain accurate after the package scope changes.

---

## 9. Keywords

The canonical minimum set is:

```json
"keywords": [
  "{PRIMARY_DOMAIN_KEYWORD}",
  "php",
  "maatify"
]
```

### 9.1 Required Rules

- Every keyword MUST be a string.
- Keywords MUST be lowercase.
- Multi-word keywords MUST use `kebab-case`.
- Duplicate keywords are forbidden.
- Every keyword MUST correspond to an implemented domain, technology, protocol, database, or stable public behavior.
- A keyword MUST NOT claim a feature that does not exist.
- Framework names MUST NOT be used by a framework-agnostic package.
- `php` MUST be present.
- `maatify` MUST be present.
- `{PRIMARY_DOMAIN_KEYWORD}` MUST be present.
- A database keyword MUST appear only when that database is part of the documented package contract or verified behavior.
- An implementation behavior MAY be a keyword only when it is a stable, meaningful package characteristic.
- Useful search synonyms MAY be included when they remain accurate.
- Keywords with Composer-special discovery meaning, such as `dev`, `testing`, or `static analysis`, MUST be added only when that classification is genuinely intended.

### 9.2 Recommended Grouping

Keywords SHOULD be ordered logically:

1. Package or domain identity.
2. Runtime technology.
3. Database, protocol, or infrastructure.
4. Primary feature terms.
5. Search synonyms.
6. PHP ecosystem identity.
7. Maatify identity.

### 9.3 Recommended Size

- A focused set SHOULD normally contain 6–15 keywords.
- Keyword stuffing is forbidden.
- Generic words such as `code`, `tool`, `software`, or `utility` SHOULD NOT be used without clear discovery value.
- GitHub Topics MAY be derived from Composer keywords but do not have to be identical.

---

## 10. Homepage, README, Type, and License

### 10.1 Homepage

The canonical form is:

```json
"homepage": "https://github.com/Maatify/{REPOSITORY_SLUG}"
```

Rules:

- The URL MUST point to the current package repository.
- It MUST NOT point to another library.
- It MUST NOT be a temporary branch or task URL.
- The Maatify corporate website belongs in author metadata, not in place of the package repository homepage under the standard profile.

### 10.2 README

The `readme` field SHOULD be omitted when the canonical file is `README.md`.

It MAY be declared only when the package intentionally uses a different valid path:

```json
"readme": "{README_FILE}"
```

The field MUST point to an existing file and MUST NOT be used to hide a missing root README.

### 10.3 Type

For libraries governed by this Standard:

```json
"type": "library"
```

Rules:

- The explicit `library` type MUST be used for Maatify consistency.
- `project`, `composer-plugin`, `metapackage`, and custom installer types are outside this profile.
- A different type requires a separate approved package profile.

### 10.4 License

The canonical field is:

```json
"license": "{LICENSE_SPDX}"
```

Rules:

- The value MUST be a valid approved SPDX identifier or valid Composer license expression.
- It MUST match the repository `LICENSE` file.
- It MUST match Packagist and GitHub metadata.
- This Standard does not force one license across all Maatify libraries.
- A license change requires an explicit legal and repository decision.

---

## 11. Authors and Support Metadata

### 11.1 Canonical Author Metadata

The canonical organization author entry is:

```json
"authors": [
  {
    "name": "Maatify",
    "email": "support@maatify.com",
    "homepage": "https://maatify.dev"
  }
]
```

Rules:

- The canonical Maatify author entry MUST be present.
- The `Maatify` casing MUST NOT be changed.
- The support email MUST NOT be changed without approval.
- The author homepage MUST NOT be changed without approval.
- Additional individuals MAY be listed only under an approved attribution policy.
- README author presentation is governed by `LIBRARY_PRESENTATION_STANDARD.md`.

### 11.2 Canonical Support Metadata

The canonical minimum block is:

```json
"support": {
  "issues": "https://github.com/Maatify/{REPOSITORY_SLUG}/issues",
  "source": "https://github.com/Maatify/{REPOSITORY_SLUG}"
}
```

Rules:

- `issues` MUST point to the current repository issue tracker.
- `source` MUST point to the current repository.
- Repository casing MUST use `Maatify`.
- Public issue tracking MUST NOT be presented as the channel for private vulnerability disclosure.
- A `security` URL SHOULD be added when a stable absolute policy URL exists:

```json
"security": "{SECURITY_POLICY_URL}"
```

- Optional support fields such as `docs`, `email`, `forum`, or `chat` MAY be added only when the endpoint is official, stable, and maintained.

---

## 12. Version Source and Release-Derived Fields

The following field MUST NOT be committed:

```json
"version": "1.0.0"
```

For VCS-distributed Maatify libraries:

- Git tags are the version source.
- Packagist derives versions from VCS.
- A manually maintained `version` field can become stale or conflict with tags.
- `time` SHOULD be omitted because release time is derived from the release source.
- Branch aliases in `extra` MAY be used only when a real branch-version requirement exists and the distribution behavior is fully understood.
- Release numbers MUST NOT be encoded into descriptions, keywords, or autoload paths.

---

## 13. Production Autoload

The canonical production mapping is:

```json
"autoload": {
  "psr-4": {
    "{ROOT_NAMESPACE}\\": "src/"
  }
}
```

Rules:

- Production PHP code MUST live under `src/`.
- `{ROOT_NAMESPACE}` MUST follow `PACKAGE_BUILDING_STANDARD.md`.
- PSR-4 MUST be the default autoload strategy.
- The namespace prefix MUST end with `\\`.
- The path MUST end with `/`.
- Host-application namespaces are forbidden.
- Tests, fixtures, and examples MUST NOT be included in production autoloading.
- A fallback empty namespace prefix MUST NOT be used.
- PSR-0 MUST NOT be used for new packages.
- `classmap` MUST NOT replace valid PSR-4 design without a documented legacy reason.
- `autoload.files` SHOULD NOT be used.
- Global functions, automatic side effects, and mandatory helper includes are forbidden by default.
- `exclude-from-classmap` MUST NOT be used to conceal incorrect package structure.

After an autoload change, the repository MUST verify the mapping using:

```bash
composer dump-autoload --optimize --strict-psr
```

The strict check applies to the current package mapping and requires optimized autoload generation.

---

## 14. Development Autoload

When namespaced test classes exist, the canonical form is:

```json
"autoload-dev": {
  "psr-4": {
    "{TEST_NAMESPACE}\\": "tests/"
  }
}
```

The normal value of `{TEST_NAMESPACE}` is `{ROOT_NAMESPACE}\Tests`.

Rules:

- Test classes MUST NOT be included in production autoloading.
- Development autoload MUST point only to development-owned paths.
- Runtime code MUST NOT depend on classes available only through `autoload-dev`.
- The field MUST be omitted if the repository has no development classes requiring autoloading.
- Bootstrap files and fixtures SHOULD NOT be placed in `autoload-dev.files`.
- The development namespace MUST remain package-specific and MUST NOT use host namespaces.

---

## 15. Runtime Requirements

The `require` field MUST contain only direct runtime contracts.

### 15.1 PHP Constraint

The canonical minimum form is:

```json
"php": ">={MINIMUM_PHP_VERSION}"
```

Rules:

- The minimum MUST be the oldest PHP minor genuinely supported.
- The constraint is a public compatibility promise.
- Every released PHP minor included by the constraint MUST be treated according to `CI_WORKFLOW_STANDARD.md`.
- The minimum MUST NOT be increased merely because a developer uses a newer local PHP version.
- An upper bound MUST NOT be added without a demonstrated incompatibility.
- A broad constraint MUST NOT claim versions that are not tested or supportable.
- README and CI claims MUST match the Composer PHP constraint.

### 15.2 PHP Extensions

The canonical form is:

```json
"ext-{RUNTIME_EXTENSION_NAME}": "*"
```

Rules:

- Every extension directly required by runtime code MUST be declared.
- `*` is permitted for `ext-*` platform packages.
- Runtime extensions MUST NOT be hidden in `require-dev`.
- An extension MUST NOT be declared if the runtime never uses it.
- The package MUST NOT assume that a common extension exists on every PHP installation.
- Extension polyfills do not remove the need to model the actual runtime contract accurately.

### 15.3 Runtime Packages

The canonical form is:

```json
"{RUNTIME_PACKAGE_NAME}": "{RUNTIME_PACKAGE_CONSTRAINT}"
```

Rules:

- Every package directly referenced by runtime code MUST be declared directly.
- The package MUST NOT rely on the host application to supply an undeclared dependency.
- Maatify shared contracts MUST use the official shared package instead of local duplication.
- A framework dependency MUST NOT be introduced into a framework-agnostic package.
- Stable tagged constraints MUST be used for release-ready libraries.
- `dev-main`, branch names, inline aliases, and stability flags are forbidden in a stable release unless an explicit temporary exception is approved.
- `*` MUST NOT be used for ordinary runtime packages.
- Exact pins SHOULD NOT be used without a documented compatibility reason.
- Caret constraints SHOULD be the default for a stable supported major line.
- Every runtime dependency MUST have a clear package-owned reason.

---

## 16. Development Requirements

The `require-dev` field is reserved for direct development, analysis, formatting, and testing tools.

Common categories include:

```json
"require-dev": {
  "friendsofphp/php-cs-fixer": "{CS_FIXER_CONSTRAINT}",
  "phpstan/phpstan": "{PHPSTAN_CONSTRAINT}",
  "phpunit/phpunit": "{PHPUNIT_CONSTRAINT}"
}
```

Rules:

- Every tool executed directly by repository scripts or CI MUST be declared directly.
- The repository MUST NOT rely on a transitive installation of a tool.
- Development tools MUST NOT be placed in `require`.
- Runtime dependencies MUST NOT be placed only in `require-dev`.
- Tool constraints MUST remain compatible with the minimum supported PHP version when the tool runs there.
- An unused tool or a tool with no maintained configuration MUST be removed.
- PHPUnit is REQUIRED when PHPUnit tests exist.
- PHPStan is REQUIRED by the Maatify package quality profile.
- A code-style tool is REQUIRED when formatting is an enforced repository check.
- Tool major-version upgrades require a compatibility review.
- Development packages MUST be alphabetically sorted.

---

## 17. Dependency Constraint Policy

| Dependency type | Default Maatify policy |
|---|---|
| PHP | Minimum-supported constraint backed by CI |
| `ext-*` | `*` |
| Stable runtime package | Caret constraint on an approved supported major |
| Stable development tool | Compatible stable constraint |
| Dev branch | Forbidden in a stable release |
| Inline alias | Forbidden in a stable release |
| Stability flag | Forbidden unless explicitly approved |
| Exact pin | Allowed only for a documented compatibility reason |
| Wildcard package constraint | Forbidden outside platform extensions |
| Unbounded unstable constraint | Forbidden |

Additional rules:

- Latest-compatible dependency resolution MUST succeed under `CI_WORKFLOW_STANDARD.md`.
- Lowest-supported dependency resolution MUST succeed under `CI_WORKFLOW_STANDARD.md`.
- Constraints MUST NOT advertise support broader than verification.
- Constraints MUST NOT be narrowed without a technical or compatibility reason.
- A dependency MUST NOT be retained after its direct use is removed.
- An abandoned dependency blocks release readiness unless an approved migration decision exists.
- Security advisories MUST NOT be bypassed by disabling audit or hiding the dependency.
- Temporary dependency workarounds MUST have an owner, reason, and removal condition.

---

## 18. Optional Package-Link Fields

Unused package-link fields MUST be omitted.

### 18.1 `conflict`

`conflict` MAY be used only for a demonstrated incompatibility.

Rules:

- Constraints MUST be precise.
- Broad ecosystem blocking is forbidden.
- Compound ranges MUST use correct logical operators.
- A conflict MUST NOT substitute for fixing an invalid dependency constraint.

### 18.2 `provide`

`provide` MAY be used for a real virtual package or capability contract.

Rules:

- The provided capability MUST be genuinely implemented.
- Virtual package names SHOULD use established ecosystem conventions.
- Providing the name of an actual package is forbidden unless the package truly ships that package's contract and behavior.

### 18.3 `replace`

`replace` is high risk.

It MUST NOT be used unless:

- The library genuinely replaces another package, or
- An approved aggregate package replaces its exact subpackages.

Rules:

- It MUST NOT be used to bypass dependency resolution.
- It MUST NOT be used to hide duplicate code.
- An aggregate replacement SHOULD use `self.version` where appropriate.
- Every use requires explicit architectural approval.

### 18.4 `suggest`

`suggest` MAY describe a genuinely optional enhancement.

Rules:

- It MUST NOT hide a required runtime dependency.
- The description MUST explain the optional capability.
- Suggested packages MUST be real and maintained.
- A suggestion MUST NOT imply automatic installation.

---

## 19. Optional Metadata and Distribution Fields

### 19.1 `funding`

`funding` MAY be added only with approved official funding destinations.

Personal, temporary, or unverified funding links are forbidden.

### 19.2 `bin`

`bin` MAY be added only when the library provides a real public CLI executable.

Rules:

- Every listed file MUST exist.
- The executable MUST be documented and tested.
- Internal convenience scripts MUST NOT be published as package binaries.
- Executables MUST NOT contain embedded credentials.

### 19.3 `extra`

`extra` MAY contain data consumed by Composer, an approved plugin, or a documented integration.

Rules:

- It MUST NOT be used as an arbitrary configuration store.
- Framework auto-discovery metadata is forbidden in a framework-agnostic library.
- Branch aliases require an actual distribution need.
- Every key MUST have a documented consumer.

### 19.4 `archive`

`archive` MAY be used only when package archive behavior requires explicit control.

Rules:

- `LICENSE`, `README.md`, and `composer.json` MUST NOT be excluded.
- Runtime source MUST NOT be excluded.
- Secrets and development artifacts MUST NOT enter the archive.
- Exclusions MUST be reviewed against the published package contents.

### 19.5 `abandoned`

`abandoned` MUST be omitted for an actively supported package.

It MAY be set only after an explicit abandonment decision. When a maintained replacement exists, the replacement package or URL SHOULD be provided.

### 19.6 `_comment`

`_comment` SHOULD NOT be used for architecture or policy.

Long-lived decisions belong in documentation. A comment MAY be used only for a narrow temporary maintenance note with a removal condition.

---

## 20. Custom Repositories

The `repositories` field MUST NOT appear in a published reusable library under the normal Maatify profile.

Forbidden entries include:

- Local `path` repositories.
- Developer workstation paths.
- Private task repositories.
- Temporary forks.
- Credential-bearing URLs.
- Environment-specific mirrors.
- Inline `package` repositories used to avoid publishing a dependency correctly.

An exception requires:

- An explicit distribution decision.
- A stable and approved source.
- No embedded credentials.
- Documentation of the effect on contributors and Packagist consumers.
- A removal or maintenance owner when the repository is temporary.

Repository declarations are root-package resolution configuration and are not inherited recursively by consumers. They MUST NOT be used as a substitute for publishing dependencies through an approved Composer repository.

---

## 21. Composer Scripts

Canonical script names, when the corresponding capability exists, are:

- `analyse`
- `format`
- `test`
- `test:unit`
- `test:regression`
- `test:integration`

### 21.1 Canonical Semantics

- `analyse` runs the repository's complete static analysis.
- `format` runs the mutating formatter.
- `test` runs the complete test suite.
- `test:unit` runs the Unit suite.
- `test:regression` runs the Regression suite.
- `test:integration` runs the Integration suite.

Example:

```json
"scripts": {
  "analyse": "phpstan analyse src tests --level=max",
  "format": "php-cs-fixer fix",
  "test": "phpunit",
  "test:unit": "phpunit --testsuite unit",
  "test:regression": "phpunit --testsuite regression",
  "test:integration": "phpunit --testsuite integration"
}
```

Rules:

- A script MUST invoke a declared command or installed binary.
- Script names MUST be lowercase.
- `:` SHOULD be used for logical grouping.
- A script MUST NOT be added when the corresponding command or suite does not exist.
- `test` MUST represent the full test suite.
- An Integration script MAY require an external service, but that requirement MUST be documented.
- Scripts MUST propagate failures.
- `|| true`, silent fallbacks, and hidden skips are forbidden.
- Scripts MUST NOT contain credentials.
- Scripts SHOULD NOT download tools from the network during normal execution.
- A non-mutating `format:check` MAY be added when adopted by the repository.
- Detailed CI invocation remains governed by `CI_WORKFLOW_STANDARD.md`.

### 21.2 Root-Only Behavior

Composer scripts are root-package behavior.

They are intended for development, verification, and release work when the library repository is the root package. The library MUST NOT require consumers to execute its root scripts after installation.

---

## 22. Lifecycle Script Safety

Lifecycle hooks such as the following SHOULD NOT be used by reusable libraries:

- `pre-install-cmd`
- `post-install-cmd`
- `pre-update-cmd`
- `post-update-cmd`
- `post-autoload-dump`

When an approved root-development hook exists, it MUST NOT:

- Modify a host application.
- Create or alter a database schema.
- Run migrations.
- Perform network downloads.
- Download or execute unverified binaries.
- Request credentials.
- Read production secrets.
- Clear a host application's cache.
- Require interactive input.
- Write outside the library repository.
- Hide failures.
- Become a required consumer installation step.

A reusable library's consumer installation MUST remain free of package-owned setup procedures.

---

## 23. Composer Configuration

The canonical configuration profile is:

```json
"config": {
  "optimize-autoloader": true,
  "sort-packages": true,
  "platform": {
    "php": "{MINIMUM_PHP_PATCH_VERSION}"
  }
}
```

### 23.1 `sort-packages`

```json
"sort-packages": true
```

This setting is REQUIRED.

It keeps package maps consistently ordered when Composer modifies them.

### 23.2 `optimize-autoloader`

```json
"optimize-autoloader": true
```

This setting SHOULD be enabled for the canonical Maatify library profile.

It is a root repository autoload-generation preference. It is not a substitute for consumer deployment optimization and does not change the package's PSR-4 contract.

### 23.3 `platform.php`

The canonical form is:

```json
"platform": {
  "php": "{MINIMUM_PHP_PATCH_VERSION}"
}
```

Rules:

- The value SHOULD represent the minimum supported PHP baseline.
- It MUST NOT be higher than the declared minimum PHP requirement.
- It MUST NOT be used to hide an incompatibility.
- Fake extension entries SHOULD NOT be added.
- If an extension must be hidden to test portability, that decision belongs to explicit CI configuration.
- Because the platform setting can emulate a version different from the executing environment, real platform requirements MUST still be verified under `CI_WORKFLOW_STANDARD.md`.
- The setting governs root dependency resolution; it does not force a consumer's PHP runtime.

### 23.4 `allow-plugins`

- If no Composer plugins are used, `allow-plugins` MAY be omitted or explicitly set to `false`.
- Every required plugin MUST be individually allowlisted.
- Allowing all plugins globally is forbidden.
- Wildcard organization approval requires a documented security reason.
- Adding a Composer plugin requires a security and necessity review.

Example:

```json
"allow-plugins": {
  "{RUNTIME_PACKAGE_NAME}": true
}
```

The placeholder MUST refer to an actual approved Composer plugin, not an ordinary package.

### 23.5 Security-Sensitive Configuration

- `secure-http` MUST NOT be disabled.
- `allow-missing-requirements` MUST NOT be enabled.
- `platform-check` MUST NOT be disabled without an explicit compatibility reason.
- `process-timeout` MUST NOT be inflated to conceal a hanging command.
- `vendor-dir` and `bin-dir` SHOULD retain Composer defaults.
- Authentication tokens, credentials, and private repository secrets MUST NOT be stored in `composer.json`.

---

## 24. Stability Policy

The canonical stable profile is:

```json
"minimum-stability": "stable",
"prefer-stable": true
```

Rules:

- A release-ready library MUST use `stable` as its minimum stability.
- `dev`, `alpha`, `beta`, or `RC` minimum stability is forbidden for a stable release.
- Per-package flags such as `@dev`, `@alpha`, `@beta`, or `@RC` are forbidden without a documented temporary exception.
- `prefer-stable` MUST NOT be treated as permission to retain unstable requirements.
- Stability settings MUST NOT be weakened to work around an incorrect constraint.
- Pre-release dependency work requires a separate approved release plan.

---

## 25. Composer Lock Policy

For reusable libraries governed by this Standard:

> **`composer.lock` MUST NOT be committed.**

Rules:

- A lock file MAY be generated temporarily during local or CI dependency resolution.
- It MUST be removed before delivery.
- A Pull Request MUST NOT add it.
- Repository integrity checks SHOULD detect it.
- The package's ignore policy SHOULD prevent accidental tracking.
- Applications have a different lock-file policy and are outside this Standard.
- Not committing the lock file does not remove dependency verification requirements.
- CI MUST verify latest-compatible and lowest-supported resolutions according to `CI_WORKFLOW_STANDARD.md`.

---

## 26. Package Distribution and Archive Safety

- `vendor/` MUST NOT be committed or shipped as package-owned source.
- `.env` files, credentials, tokens, private keys, and machine-specific configuration MUST NOT be published.
- IDE metadata and local task artifacts MUST NOT be part of the package distribution.
- `composer.lock` MUST NOT be present in the reusable-library source distribution.
- Runtime source, `composer.json`, `README.md`, and `LICENSE` MUST remain available.
- Tests and documentation MAY remain in source distributions.
- Generated archives MUST be inspected when custom archive exclusions exist.
- Package installation MUST NOT depend on files ignored by VCS or excluded from the distribution.

---

## 27. Metadata Synchronization

The following contracts MUST remain synchronized:

- `name` matches the published package identity.
- `description` is semantically consistent with the GitHub description.
- `keywords` and GitHub Topics do not contradict each other.
- `homepage`, `support.source`, and `support.issues` target the current repository.
- `license` matches `LICENSE` and repository metadata.
- The PHP constraint matches README and CI support claims.
- Runtime dependencies match README requirements and actual runtime use.
- Composer scripts match maintained contributor commands.
- Packagist badges use the correct Composer package name.
- The package reference does not claim undeclared runtime dependencies.
- `authors` remains consistent with approved organization metadata.

`composer.json` is the source of truth for the Composer package contract, but every claim inside it MUST be supported by runtime code, documentation, and verification.

---

## 28. Canonical Composer Templates

### 28.1 Minimal Publishable Library

This template contains no empty fields:

```json
{
  "name": "maatify/{PACKAGE_SLUG}",
  "description": "{PACKAGE_DESCRIPTION}",
  "keywords": [
    "{PRIMARY_DOMAIN_KEYWORD}",
    "php",
    "maatify"
  ],
  "homepage": "https://github.com/Maatify/{REPOSITORY_SLUG}",
  "type": "library",
  "license": "{LICENSE_SPDX}",
  "authors": [
    {
      "name": "Maatify",
      "email": "support@maatify.com",
      "homepage": "https://maatify.dev"
    }
  ],
  "support": {
    "issues": "https://github.com/Maatify/{REPOSITORY_SLUG}/issues",
    "source": "https://github.com/Maatify/{REPOSITORY_SLUG}"
  },
  "autoload": {
    "psr-4": {
      "{ROOT_NAMESPACE}\\": "src/"
    }
  },
  "require": {
    "php": ">={MINIMUM_PHP_VERSION}"
  },
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true,
    "platform": {
      "php": "{MINIMUM_PHP_PATCH_VERSION}"
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

### 28.2 Test-Enabled Extension

Add only when test classes and direct development tools exist:

```json
{
  "autoload-dev": {
    "psr-4": {
      "{TEST_NAMESPACE}\\": "tests/"
    }
  },
  "require-dev": {
    "friendsofphp/php-cs-fixer": "{CS_FIXER_CONSTRAINT}",
    "phpstan/phpstan": "{PHPSTAN_CONSTRAINT}",
    "phpunit/phpunit": "{PHPUNIT_CONSTRAINT}"
  },
  "scripts": {
    "analyse": "phpstan analyse src tests --level=max",
    "format": "php-cs-fixer fix",
    "test": "phpunit",
    "test:unit": "phpunit --testsuite unit",
    "test:regression": "phpunit --testsuite regression",
    "test:integration": "phpunit --testsuite integration"
  }
}
```

### 28.3 Runtime Requirement Extension

Add only direct runtime contracts:

```json
{
  "require": {
    "ext-{RUNTIME_EXTENSION_NAME}": "*",
    "{RUNTIME_PACKAGE_NAME}": "{RUNTIME_PACKAGE_CONSTRAINT}",
    "php": ">={MINIMUM_PHP_VERSION}"
  }
}
```

Template rules:

- These snippets MUST be merged into one valid final JSON object.
- Duplicate top-level keys are forbidden.
- Empty objects MUST NOT be retained.
- Unused fields MUST be omitted.
- Runtime extensions and packages MUST reflect actual use.
- Development tools MUST reflect actual repository commands.
- The final file MUST contain no comments, placeholders, or trailing commas.

---

## 29. Validation Requirements

Every new or modified `composer.json` MUST pass:

```bash
composer validate --strict
```

When autoload mappings change, it MUST also pass:

```bash
composer dump-autoload --optimize --strict-psr
```

Review MUST verify:

- Valid JSON.
- Composer schema validity.
- Valid package name.
- Accurate description.
- Valid license expression.
- Valid and current support URLs.
- Correct PSR-4 mappings.
- Declared PHP compatibility.
- Direct dependency completeness.
- Correct separation of runtime and development requirements.
- Real script commands and suites.
- No committed `version` field.
- No committed `composer.lock`.
- No unapproved custom repositories.
- No unstable release constraints.
- No credentials or private environment paths.
- No unsafe lifecycle hooks.
- No copied package or repository identity.

Automated verification of latest dependencies, lowest dependencies, platform requirements, audits, abandoned packages, and quality gates belongs to `CI_WORKFLOW_STANDARD.md`.

---

## 30. Composer Review Checklist

### Identity and Metadata

- [ ] Package name uses the `maatify` vendor.
- [ ] Package name is lowercase and uses `kebab-case`.
- [ ] Package slug matches the repository slug or the difference is documented.
- [ ] Description accurately states the current package purpose.
- [ ] Keywords are focused, relevant, lowercase, and non-duplicated.
- [ ] `php` and `maatify` keywords are present.
- [ ] The primary domain keyword is present.
- [ ] Homepage points to the current repository.
- [ ] Type is `library`.
- [ ] License matches `LICENSE`.
- [ ] Canonical Maatify author metadata is present.
- [ ] Support URLs point to the current repository.
- [ ] Security reporting is not directed to public issues.
- [ ] No `version` field is committed.

### Autoload

- [ ] Production autoload uses PSR-4.
- [ ] Production autoload points only to `src/`.
- [ ] Namespace and paths end with the required separators.
- [ ] No host namespace exists.
- [ ] Tests are excluded from production autoload.
- [ ] Development autoload points to `tests/` when applicable.
- [ ] Runtime code does not depend on development autoload.
- [ ] Strict PSR autoload validation succeeds after mapping changes.

### Requirements and Constraints

- [ ] PHP minimum matches README and CI.
- [ ] Every directly used PHP extension is declared.
- [ ] Every direct runtime package is declared.
- [ ] No runtime dependency is hidden in `require-dev`.
- [ ] No development tool is placed in `require`.
- [ ] No dependency relies accidentally on transitive installation.
- [ ] Package maps are alphabetically sorted.
- [ ] Stable constraints are used.
- [ ] No dev branches, inline aliases, or unstable flags remain.
- [ ] Exact pins have a documented reason.
- [ ] No unused dependency remains.
- [ ] Optional package-link fields are semantically correct.
- [ ] `replace` and `provide` have explicit architectural justification.

### Scripts and Configuration

- [ ] Composer scripts map to real declared commands.
- [ ] Suite scripts exist only for real suites.
- [ ] `test` runs the full test suite.
- [ ] Scripts propagate failures.
- [ ] Scripts contain no credentials.
- [ ] No unsafe lifecycle hooks exist.
- [ ] `sort-packages` is enabled.
- [ ] `platform.php` matches the minimum supported baseline.
- [ ] `optimize-autoloader` follows the approved profile.
- [ ] Composer plugins are explicitly allowlisted when present.
- [ ] `secure-http` is not disabled.
- [ ] `allow-missing-requirements` is not enabled.
- [ ] Default vendor and binary directories are preserved unless justified.

### Stability, Distribution, and Validation

- [ ] `minimum-stability` is `stable`.
- [ ] `prefer-stable` is enabled.
- [ ] No custom repository exists without approval.
- [ ] No credentials, local paths, or private task URLs exist.
- [ ] `vendor/` is not committed.
- [ ] `composer.lock` is not committed.
- [ ] Package archive rules preserve runtime source and required legal metadata.
- [ ] `composer validate --strict` succeeds.
- [ ] Composer metadata matches README, package reference, GitHub metadata, and CI claims.
- [ ] Latest-compatible dependency verification succeeds in CI.
- [ ] Lowest-supported dependency verification succeeds in CI.
- [ ] Platform-requirement verification succeeds in CI.
- [ ] Composer audit and abandoned-package policy succeed in CI.

---

## 31. Non-Goals

This Standard does not force:

- The same runtime dependencies on every library.
- The same keywords.
- The same development-tool versions.
- Test suites that do not apply.
- A framework dependency.
- A database dependency.
- Composer plugin behavior.
- Application lock-file policy.
- GitHub Actions implementation.
- README visual formatting.
- Package-specific runtime architecture.
- A custom repository.
- A binary executable.
- Verbatim copying of another library's `composer.json`.

---

## 32. Reference Basis

This Standard is intentionally stricter than Composer's general schema where Maatify requires consistent reusable-library behavior.

Primary Composer references:

- Composer schema: `https://getcomposer.org/doc/04-schema.md`
- Composer basic usage and lock files: `https://getcomposer.org/doc/01-basic-usage.md`
- Composer CLI and strict PSR validation: `https://getcomposer.org/doc/03-cli.md`
- Composer configuration: `https://getcomposer.org/doc/06-config.md`

Official Composer documentation remains authoritative for Composer mechanics. This Standard remains authoritative for Maatify package policy.
