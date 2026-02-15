# Introduction

**Maatify/ContentDocuments** is a kernel-grade module designed to manage the lifecycle, versioning, translation, and acceptance tracking of static content documents.

## Purpose

Applications often require users to accept legal agreements (Terms of Service, Privacy Policy) or informational documents (Refund Policy, Community Guidelines). These requirements share common complexities:
1.  **Versioning:** When "Terms" change, users must accept the *new* version.
2.  **Localization:** The content exists in multiple languages, but the *legal acceptance* is tied to the version, not the translation.
3.  **Auditability:** Acceptance records must be immutable and traceable.
4.  **Decoupling:** The document system should not depend on the specific user table structure (e.g., `users`, `admins`, `partners`).

This module solves these problems with a strict, self-contained architecture.

## Core Philosophy

1.  **Actor Agnostic:** The system does not know what a "User" is. It tracks acceptance via a generic `actor_type` and `actor_id`.
2.  **Explicit Versioning:** "Terms of Service" is a *Type*. "v1.0" and "v2.0" are *Documents*. Content lives in *Translations*.
3.  **One Active Version:** The system enforces that only one version of a Document Type can be active at a time.
