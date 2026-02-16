# Security Policy

## Supported Versions

The following versions of the library are currently supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability within this project, please send an email to the security team at **security@maatify.dev**. All security vulnerabilities will be promptly addressed.

Please do not open public issues for security vulnerabilities.

## Responsible Disclosure

We ask that you do not publicly disclose the issue until we have had a chance to address it. We will make every effort to resolve the issue in a timely manner.

## Security Guarantees

This library provides the following security guarantees:

1.  **Severity Integrity:** Critical system errors (5xx) cannot be accidentally masked or downgraded to client errors (4xx) by wrapping them in business logic exceptions.
2.  **Taxonomy Enforcement:** Error categories are immutable and strictly typed, preventing attackers from manipulating error responses to hide system failures.
3.  **Strict Typing:** All exception handling relies on strict PHP typing, minimizing the risk of type confusion vulnerabilities.

## Out of Scope

The following are considered out of scope for this library:

*   **Transport Layer Security:** This library does not handle HTTP transport or TLS encryption.
*   **Web Framework Integration:** While compatible with any framework, security issues arising from improper integration (e.g., exposing stack traces in production) are the responsibility of the application developer.
*   **Message Content:** The library does not sanitize exception messages. Developers must ensure sensitive data is not included in exception messages.
