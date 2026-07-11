# Maatify PHP Library Repository Presentation Standard

## 1. Normative Language

The key words "MUST", "MUST NOT", "REQUIRED", "SHOULD", "SHOULD NOT", "MAY", and "OPTIONAL" in this document are to be interpreted as described in RFC 2119.

* `MUST / MUST NOT`: These are binding rules.
* `SHOULD / SHOULD NOT`: These represent the default expected behavior. Any deviation requires a documented reason.
* `MAY`: This is an optional choice allowed depending on the nature of the library.

## 2. Scope and Relationship to Other Standards

This Standard is responsible for governing:
* Repository presentation.
* README visual and information architecture.
* Badges.
* Governance document identity.
* Release-facing documentation state.
* Author and ecosystem identity.
* GitHub description, topics and PR metadata.
* Final Release Candidate presentation review.

It explicitly does **not** govern:
* Runtime architecture.
* Public API design.
* Database behavior.
* Exception hierarchy.
* Test architecture.
* CI implementation.
* Dependency constraints.

### Relationship to other standards:
* `PACKAGE_BUILDING_STANDARD.md`: Governs library building, code architecture, and the package contract.
* `CI_WORKFLOW_STANDARD.md`: Governs CI, quality gates, and automated testing.
* `LIBRARY_PRESENTATION_STANDARD.md`: Governs appearance, identity, public documentation, and release-facing presentation.

Each Standard has clear boundaries and must not duplicate the content of another.

## 3. Applicability

The current version of this Standard applies exclusively to:
`Maatify standalone PHP Composer libraries`

It MUST NOT be generalized to JavaScript, Rust, or any other language.
Every other language or ecosystem MUST have a separate Standard when needed.

The canonical footer approved for this Standard is:
```markdown
<div align="center">

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)

</div>
```
Do not replace `PHP Libraries` with `Software Libraries` or any other generic term.

## 4. Canonical Placeholders

The examples in this Standard use canonical placeholders. When applying these templates, the placeholders MUST be replaced with actual values.

* `{PACKAGE_DISPLAY_NAME}`: The human-readable display name of the package.
* `{COMPOSER_PACKAGE_NAME}`: The full Composer package name (e.g., `vendor/package`).
* `{REPOSITORY_SLUG}`: The repository name within the Maatify organization.
* `{PACKAGE_BADGE_NAME}`: The display name used inside badges.
* `{PACKAGE_BADGE_TOKEN}`: The text used within shields.io badges (note that hyphens `-` in the text might need to be encoded as `--`).
* `{PACKAGE_REFERENCE_FILE}`: The specific reference markdown file for the package.
* `{MINIMUM_PHP_VERSION}`: The minimum supported PHP version.
* `{SUPPORTED_MAJOR_LINE}`: The actively supported release line.
* `{RELEASE_VERSION}`: The version number for the release.
* `{RELEASE_DATE}`: The date of the release.
* `{BADGE_AREA}`: The complete rendered badge groups selected for the current library according to the badge architecture defined by this Standard.
* `{PACKAGE_SUMMARY}`: A concise and technically accurate summary of the library's primary purpose and supported scope.
* `{ENCODED_COMPOSER_PACKAGE_NAME}`: The URL-encoded Composer package name used inside shields.io badge text, with `/` encoded as `%2F`.

*Note: This standard MUST NOT contain hardcoded names of specific existing packages as default templates. Always use the placeholders.*

## 5. Core Presentation Principles

1. Every library MUST clearly look like a part of the Maatify ecosystem.
2. The first screen of the README MUST communicate:
   * The library name.
   * Its ecosystem identity.
   * Its primary purpose.
   * The package status.
   * How to install or access it.
3. The presentation MUST NOT claim features, support, or a quality status that is not proven.
4. The shared identity MUST NOT erase the functional differences between libraries.
5. A Release Candidate MUST be visually and documentarily ready before creating a Tag.
6. Copying the README or governance files from another library without replacing all names and links is strictly forbidden.
7. The GitHub-rendered appearance is the ultimate reference, not just the raw Markdown source.
8. Presentation changes MUST NOT alter runtime contracts.

## 6. Required Release-Facing Files

Any Maatify PHP library ready for release MUST contain the following files (where applicable):

* `README.md`: Quickly introduces the library.
* `{PACKAGE_REFERENCE_FILE}`: The detailed source of truth for contracts.
* `CHANGELOG.md`: Documents releases.
* `SECURITY.md`: Defines support, reporting, and scope.
* `CONTRIBUTING.md`: Explains contribution and local verification.
* `CODE_OF_CONDUCT.md`: Establishes community rules.
* `LICENSE`: The package license.
* `composer.json`: The package definition.

The `README.md` MUST NOT be overloaded with all the details present in the Package Reference.

## 7. README Header Standard

By default, the README header MUST contain:
1. Package display name.
2. Maatify logo.
3. Badge area.
4. Package summary.
5. A separator before the detailed content.

### Canonical Template:
```markdown
<div align="center">

# {PACKAGE_DISPLAY_NAME}

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

{BADGE_AREA}

{PACKAGE_SUMMARY}

</div>

---
```

* Center alignment is the recommended default. It MAY be deviated from only with a documented reason.
* The logo link MUST point to the approved Maatify source.
* Logos of other libraries MUST NOT be added.
* The package summary MUST be concise and accurate.

## 8. README Badge Architecture

Badges MUST be divided into logical groups.

### 8.1 Package Status
For a Final Release Candidate of a Composer library published or intended to be published on Packagist, the following MUST be prepared:
* Latest Version.
* PHP Version.
* License.
* PHPStan Level Max (as long as it is actually proven in the project).

### 8.2 Documentation
Clear badges or links MUST be prepared for:
* Changelog.
* Package Reference.
* Security Policy.
* Contributing Guide.

### 8.3 Ecosystem and Adoption
Where applicable, the following MUST be prepared:
* Monthly Downloads.
* Total Downloads.
* Maatify Ecosystem.
* Install.

### 8.4 Release Candidate Rule
> Final Release Candidate MUST include the complete release-facing badge set before Tag creation when the package is intended to be published immediately after owner approval.

This rule applies only when Packagist is the library's selected package registry or when immediate Packagist publication is part of the approved release plan.

It is forbidden to remove:
* Latest Version.
* Monthly Downloads.
* Total Downloads.

simply because the first Tag has not been created yet, provided the task is preparing the Final Release Candidate for direct publication after approval.
However, it is forbidden to write an incorrect text claim that a version is already published before it happens.

### 8.5 Badge Style
This standard does not force `style=for-the-badge` on the README.
* Small badges MAY be used.
* `for-the-badge` MAY be used.
* The standard is visual consistency.
* Badge groups MUST NOT mix sizes and styles randomly.
* The final selection MUST be reviewed against GitHub rendering.
* The exact number of lines is not strictly required to be identical across libraries.

### 8.6 Badge Accuracy
Every Badge MUST:
* Refer to the current library.
* Use the correct Composer package name.
* Use the correct Repository slug.
* Link to the correct local file or external page.
* Not point to a different reference library.
* Not claim a quality gate that does not exist.
* Not claim a License different from the actual one.

## 9. Canonical Composer / Packagist Badge Templates

### Package Status
```markdown
[![Latest Version](https://img.shields.io/packagist/v/{COMPOSER_PACKAGE_NAME}.svg)](https://packagist.org/packages/{COMPOSER_PACKAGE_NAME})
[![PHP Version](https://img.shields.io/packagist/php-v/{COMPOSER_PACKAGE_NAME}.svg)](https://packagist.org/packages/{COMPOSER_PACKAGE_NAME})
[![License](https://img.shields.io/packagist/l/{COMPOSER_PACKAGE_NAME}.svg)](LICENSE)
```
*(Plus PHPStan where proven)*

### Ecosystem and Usage
```markdown
[![Monthly Downloads](https://img.shields.io/packagist/dm/{COMPOSER_PACKAGE_NAME})](https://packagist.org/packages/{COMPOSER_PACKAGE_NAME})
[![Total Downloads](https://img.shields.io/packagist/dt/{COMPOSER_PACKAGE_NAME})](https://packagist.org/packages/{COMPOSER_PACKAGE_NAME})
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify)
[![Install](https://img.shields.io/badge/Install-composer%20require%20{ENCODED_COMPOSER_PACKAGE_NAME}-blue)](https://packagist.org/packages/{COMPOSER_PACKAGE_NAME})
```
*Note: Ensure proper URL encoding for the install badge (e.g., `%2F` for `/`).*

### Documentation
```markdown
[![Changelog](https://img.shields.io/badge/Changelog-View-blue.svg)](CHANGELOG.md)
[![Package Reference](https://img.shields.io/badge/Reference-Read-blue.svg)]({PACKAGE_REFERENCE_FILE})
[![Security Policy](https://img.shields.io/badge/Security-Policy-blue.svg)](SECURITY.md)
[![Contributing Guide](https://img.shields.io/badge/Contributing-Guide-blue.svg)](CONTRIBUTING.md)
```

## 10. README Section Architecture

The general default order of sections is:
1. Header and Identity
2. Package Summary
3. Key Features
4. Requirements
5. Installation
6. Quick Usage / Usage
7. Public Runtime API
8. Critical Runtime Behavior
9. Architecture Guarantees
10. Exception and Error Propagation
11. Security and Trust Boundaries
12. Examples
13. Documentation
14. Quality Status
15. Development and Testing
16. License
17. Author
18. Maatify Footer

### Required Sections
Any release-ready library MUST include:
* Package Summary.
* Key Features.
* Requirements.
* Installation.
* Usage or Quick Usage.
* Documentation.
* Quality Status.
* License.
* Author.
* Maatify Footer.

### Conditional Sections
These sections are added only when they apply:
* Public Runtime API.
* Critical Runtime Behavior.
* Architecture Guarantees.
* Exception and Error Propagation.
* Security and Trust Boundaries.
* Examples.
* Schema.
* Integration Testing.
* Migration Guide.
* Upgrade Notes.

Do not create empty sections merely to satisfy a template.

## 11. Heading and Emoji Rules

* Emoji MAY be used.
* If used, they MUST be consistent across parallel headings.
* Do not use emoji randomly in some sections while leaving similar sections unstyled.
* The heading hierarchy MUST be semantically correct.
* There MUST be one logical `#` main heading.
* Separators `---` MAY be used between major groups, but without excess.
* The presentation MUST NOT become cluttered or overly decorative at the expense of clarity.

## 12. Runtime and Documentation Accuracy

The Presentation Standard does not allow altering technical facts.
The README MUST:
* State the actual runtime requirements.
* State the actual dependencies.
* Describe the actually supported databases.
* Clarify concurrency, transaction, or error propagation when public contracts require it.
* Distinguish between package-defined exceptions and external throwables.
* Not omit critical contracts for the sake of appearance.
* Not copy runtime claims from another library.
* Link to the reference documentation for lengthy details.

## 13. Governance Document Identity Badges

Standard identity badges MUST be present in specific locations.

### 13.1 `CODE_OF_CONDUCT.md`
Defaults to starting with:
```markdown
# Code of Conduct — {COMPOSER_PACKAGE_NAME}

[![Maatify {PACKAGE_BADGE_NAME}](https://img.shields.io/badge/Maatify-{PACKAGE_BADGE_TOKEN}-blue?style=for-the-badge)](https://github.com/Maatify/{REPOSITORY_SLUG})
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)
```

### 13.2 `SECURITY.md`
Defaults to starting with:
```markdown
# Security Policy

[![Maatify {PACKAGE_BADGE_NAME}](https://img.shields.io/badge/Maatify-{PACKAGE_BADGE_TOKEN}-blue?style=for-the-badge)](https://github.com/Maatify/{REPOSITORY_SLUG})
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)
```

### Mandatory Rules
* `style=for-the-badge` MUST be used in these two locations.
* The first badge MUST point to the current repository.
* The Ecosystem badge MUST point to `https://github.com/Maatify`.
* The name or repository link of another library MUST NOT be used.
* A third badge MUST NOT be added without a documented decision.
* The large Maatify logo MUST NOT be added inside these files.
* Do not copy errors or links from an older reference file.

### Approved Locations
Identity badges are required by default in:
* `CODE_OF_CONDUCT.md`
* `SECURITY.md`

They are NOT automatically added to:
* `CHANGELOG.md`
* `CONTRIBUTING.md`
* `LICENSE`
Unless a subsequent decision alters this Standard.

## 14. SECURITY Presentation States

There are two defined states for the Security Policy:

### 14.1 Development State
When no approved version and no Final Release Candidate exist, the file MAY state that there is currently no supported release line.

### 14.2 Final Release Candidate State
When the library is prepared for direct release upon owner approval, the Security Policy MUST be prepared for the target release format:

```markdown
## Supported Versions

The actively supported release line is `{SUPPORTED_MAJOR_LINE}`.

Security fixes are provided in the latest stable release within the supported `{SUPPORTED_MAJOR_LINE}` line. Users should upgrade to the latest available `{SUPPORTED_MAJOR_LINE}` version before reporting a vulnerability.
```

A table MAY be used:
```markdown
| Version | Supported |
|---------|-----------|
| `{SUPPORTED_MAJOR_LINE}` | Yes |
| Older lines | No |
```

The Final Release Candidate presentation sets up the final state before the Tag, provided that:
* There is a clear release intent.
* Approval of the polish immediately precedes the release.
* If the version or date changes before publication, the files MUST be updated before the Tag.

## 15. CHANGELOG Presentation Standard

The CHANGELOG MUST follow these rules:
* Keep a Changelog format.
* Semantic Versioning.
* `[Unreleased]` MUST always be present at the top.
* The final version and its date MUST be present.
* Release links MUST be at the bottom of the file.
* Do not list features that do not exist.
* Do not describe future changes as already implemented.
* A Final Release Candidate MAY carry the final version number and date before the Tag, provided publication will occur immediately after approval.

### Template:
```markdown
## [Unreleased]

## [{RELEASE_VERSION}] - {RELEASE_DATE}
```

### Links Template:
```markdown
[Unreleased]: https://github.com/Maatify/{REPOSITORY_SLUG}/compare/v{RELEASE_VERSION}...HEAD
[{RELEASE_VERSION}]: https://github.com/Maatify/{REPOSITORY_SLUG}/releases/tag/v{RELEASE_VERSION}
```

## 16. CONTRIBUTING Presentation

The `CONTRIBUTING.md` file MUST clarify:
* Package identity.
* Package boundaries.
* Ways to contribute.
* Local verification commands.
* Test and Integration requirements.
* Pull Request expectations.
* Architecture discussion requirements.
* Security-reporting route.
* `composer.lock` policy (according to the library's nature).

Identity badges are NOT forced inside this file in the current version of the Standard.
The file MUST NOT preemptively enforce a GitHub Issue for every change unless this is an approved repository policy.

## 17. Canonical Author Block

The following Author block is approved for Maatify PHP libraries:

```markdown
## 👤 Author

Engineered by **Mohamed Abdulalim** ([@megyptm](https://github.com/megyptm))<br>
Backend Lead & Technical Architect<br>
[https://www.maatify.dev](https://www.maatify.dev)
```

### Formatting Rules
* Use `<br>` for mandatory line breaks.
* Do not use hidden trailing spaces as a line break mechanism.
* Do not alter the name.
* Do not alter the GitHub username.
* Do not alter the title.
* Do not alter the Maatify link without explicit permission.

If the library does not use Emoji in headings, it is acceptable to use:
```markdown
## Author
```
while keeping the content of the section unchanged.

## 18. Canonical Maatify Footer

The absolutely final element in the README MUST be:

```markdown
---

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)
```

No text may appear after it.
Do not replace `Modern PHP Libraries` with any other generic wording within this Standard.

## 19. Package and Repository Metadata

This Standard covers presentation-facing consistency only.

### Composer Metadata

The canonical construction and validation rules for Composer metadata are defined by [COMPOSER_PACKAGE_STANDARD.md](COMPOSER_PACKAGE_STANDARD.md).

This section governs only presentation-facing consistency between Composer, README, Packagist, and GitHub metadata.

* `description` MUST remain accurate and consistent with the public package presentation.
* `keywords` MUST remain relevant and MUST NOT contradict GitHub Topics.
* `homepage` MUST point to the current repository.
* Author metadata MUST match the approved Maatify identity.
* License metadata MUST match `LICENSE`.

Dependency declarations, constraints, autoloading, scripts, configuration, stability, and lock-file policy are governed exclusively by [COMPOSER_PACKAGE_STANDARD.md](COMPOSER_PACKAGE_STANDARD.md).

### GitHub Metadata
The following MUST be reviewed:
* Repository description.
* Website.
* Topics.
* Releases visibility.
* Packages visibility (when applicable).

Topics or descriptions MUST NOT claim features that do not exist.

## 20. Pull Request Presentation Metadata

Any PR for a Final Release Candidate MUST contain:
* A Title reflecting the target release.
* A Body describing the actual changes.
* A Scope confirmation.
* A Release-control statement.
* Confirmation that no Merge, Tag, or automatic Release is performed.
* No outdated wording that contradicts the branch state.

The PR body MUST NOT state:
`no release exists`
`date omitted`
`pre-release state retained`
if the files inside are formatted as a final release.

If the PR scope changes, the Title and Body MUST be updated to remain an accurate historical record.

## 21. Visual Review Rules

Before presentation is considered ready, the following MUST be verified:
1. Review the GitHub-rendered README.
2. Ensure badges are not clustered messily.
3. Verify badge size consistency.
4. Check all links.
5. Verify the Composer name and Repository slug in every Badge.
6. Ensure the logo renders correctly.
7. Verify the heading hierarchy.
8. Verify the Author line breaks (`<br>`).
9. Verify the Footer is the very last element.
10. Ensure a final newline exists.
11. Ensure there are no names or links belonging to other libraries.
12. Ensure no Runtime contracts were deleted during the visual polish.

## 22. Anti-Copy and Repository Isolation Rules

When using another library as a visual reference, the implementer MUST NOT copy:
* Package names.
* Repository URLs.
* Composer names.
* Badge labels.
* Security links.
* Author variants.
* Runtime claims.
* Dependencies.
* Database support.
* Release version.
* Release date.

An explicit search for reference repository names MUST be conducted before submission.

## 23. Final Release Candidate Checklist

* [ ] README header and Maatify identity are present.
* [ ] Required badges exist and point to the current package.
* [ ] Packagist badges are complete when the library uses or is being prepared for immediate publication on Packagist.
* [ ] README sections match the package's actual behavior.
* [ ] Critical runtime contracts remain documented.
* [ ] CODE_OF_CONDUCT identity badges are correct.
* [ ] SECURITY identity badges are correct.
* [ ] SECURITY supported release line is correct.
* [ ] CHANGELOG contains `[Unreleased]`.
* [ ] CHANGELOG contains the target version and date.
* [ ] Release links target the current repository.
* [ ] CONTRIBUTING reflects actual local verification.
* [ ] Author block uses visible `<br>` line breaks.
* [ ] The canonical PHP-library footer is the final README element.
* [ ] Composer and GitHub metadata are accurate.
* [ ] PR title and body match the actual release-candidate state.
* [ ] No foreign package names or URLs remain.
* [ ] No `composer.lock` was introduced when the library does not track it.
* [ ] CI Gate is successful.
* [ ] No Merge, Tag, Release, or Packagist action occurred without owner approval.

## 24. Non-Goals

This Standard explicitly does NOT force:
* The exact same Runtime sections on every library.
* The exact same number of examples.
* The exact same number of Documentation links.
* The exact literal Emoji set.
* `for-the-badge` inside the README.
* Packagist badges on a library not utilizing Packagist.
* Database or framework claims.
* Empty sections.
* Verbatim README copying from another library.
* Runtime changes during presentation work.
