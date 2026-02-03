# Crypto DX Module

## Overview

The **Crypto DX (Developer Experience)** module is an orchestration layer designed to simplify the usage of the system's strict cryptographic primitives.

It integrates the following isolated modules into unified pipelines:
* **KeyRotation**: Root key management.
* **HKDF**: Key derivation.
* **Reversible**: AES-GCM encryption.

## Purpose

The primary goals of this module are:
1. **Simplicity**: Provide a single entry point (`CryptoProvider`) for application developers.
2. **Safety**: Ensure pipelines are wired correctly (e.g., ensuring HKDF is used with proper contexts).
3. **Consistency**: Standardize how crypto services are instantiated across the codebase.

### What it is NOT
* It is **NOT** a new cryptographic library.
* It does **NOT** implement encryption algorithms (AES, Argon2, etc.).
* It does **NOT** manage key storage or environment variables.
* It does **NOT** replace the underlying modules; it merely uses them.

## Architecture

This module introduces:
* **Factories** (`CryptoContextFactory`, `CryptoDirectFactory`): To handle the complex dependency injection wiring.
* **Facade** (`CryptoProvider`): To expose a clean API for consuming services.

For detailed architecture decisions, see [ADR-005](./docs/ADR-005-Crypto-DX-Layer.md).

## Usage

See [HOW_TO_USE.md](./docs/HOW_TO_USE.md) for code examples and integration patterns.
