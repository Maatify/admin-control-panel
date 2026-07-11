# Security Policy

[![Maatify Persistence](https://img.shields.io/badge/Maatify-PERSISTENCE-blue?style=for-the-badge)](https://github.com/Maatify/persistence)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)

## Supported Versions

The actively supported release line is `1.x`.

Security fixes are provided in the latest stable release within the supported `1.x` line. Users should upgrade to the latest available `1.x` version before reporting a vulnerability.

| Version | Supported |
|---------|-----------|
| `1.x`   | Yes       |
| `< 1.0` | No        |

## Reporting a Vulnerability

Please report vulnerabilities privately by emailing [support@maatify.com](mailto:support@maatify.com).

Do **not** publish exploitable details in a public issue or pull request.

When reporting a vulnerability, please include where possible:
* Affected package version or commit SHA
* Steps to reproduce
* Impact
* Suggested mitigation

## Security Scope

The scope of this security policy is strictly limited to the boundaries of the `maatify/persistence` package. Security reports may be relevant when they involve:

* Unsafe SQL identifier validation or quoting within the package logic.
* SQL injection vulnerabilities inside package-owned query construction.
* Failures in scope isolation (e.g., unintended movement of rows across different scopes).
* Modification of rows outside the intended scope or affected range.
* Incorrect transaction ownership behavior.
* Failure to rollback an owned transaction during an exception or error.
* Incorrect handling of soft-delete filtering.
* Unsafe handling of external throwables during operations.
* Unexpected disclosure of sensitive information through package exception behavior.
* Vulnerabilities in runtime dependencies that directly affect the package.

## Out of Scope

The following areas are explicitly out of scope for this package's security policy:

* Host application authentication and authorization mechanisms.
* Host application routing, middleware, controllers, or user interfaces.
* Host database credentials and connection security.
* PDO configuration and instantiation owned by the consuming application.
* Server, firewall, deployment, or infrastructure configuration.
* MySQL user privileges or configuration managed by the host environment.
* Misuse of the package that contradicts the documented contracts and intended usage.
* External schema modifications or data corruption not caused by the package itself.

## Disclosure Policy

Once a security vulnerability is reported via `support@maatify.com`, the Maatify team will:
1. Acknowledge receipt of the vulnerability report.
2. Review and investigate the report to determine impact and scope.
3. Keep the reporter updated on the status of the investigation.
4. Prepare and test a security patch when deemed necessary.
5. Issue a patched version when appropriate.
6. A security advisory may be published if the issue affects a published version and public disclosure is warranted.
