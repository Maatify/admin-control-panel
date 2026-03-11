# Chapter 1: Overview

The `Maatify\SharedCommon` module contains foundational contracts and abstractions intended to be shared across all Maatify modules, such as `AdminKernel` and `Verification`. It acts as the backbone defining "how we communicate" core system realities, ensuring consistent behavior, predictability, and reliable testing throughout the entire application ecosystem.

## Core Goals

- **Framework Agnosticism:** By depending on `SharedCommon` rather than framework-specific implementations (e.g., global functions like `time()` or `date()`), modules remain entirely decoupled and reusable across different frameworks.
- **Unified Interfaces:** The module provides standardized interfaces for cross-cutting concerns like time management, security event tracking, and telemetry data collection. This means any module that needs to log an IP address or track a request ID knows exactly which contract to ask.
- **Predictable Testing:** Establishing a strict `ClockInterface` allows applications to freeze time during testing, ensuring consistent assertions without mocking global PHP functions or relying on fragile timing conditions.
- **Telemetry and Security Standardization:** Interfaces like `TelemetryContextInterface` and `SecurityEventContextInterface` guarantee that any module (such as the `AuditTrail` or `Verification` modules) will receive consistent, well-structured telemetry data (IPs, User Agents, Request IDs) without knowing how the HTTP request is built.

## Typical Use Cases

- **Time Tracking:** Safely generating a new OTP token with a precise expiry time using `ClockInterface->now()`.
- **Security Auditing:** Accessing the client IP and User-Agent through `SecurityEventContextInterface` to track failed login attempts or session manipulations.
- **Request Tracing:** Extracting a Request ID via `TelemetryContextInterface` to correlate logs across disparate modules during a single HTTP cycle.

## High-Level Workflow

1.  **Contract Definition:** `SharedCommon` defines the interfaces (the "contracts") inside `Maatify\SharedCommon\Contracts`.
2.  **Implementation Implementation:** `SharedCommon` provides basic implementations (like `SystemClock` in `Infrastructure`) or expects the broader application to fulfill the contracts (like `TelemetryContextInterface`).
3.  **Module Dependency:** Modules like `Verification` require `ClockInterface` to manage their internal TTLs and `TelemetryContextInterface` for IP auditing.
4.  **Container Binding:** The `Bootstrap` layer registers these interfaces, binding them to real infrastructure objects provided by the underlying framework.
5.  **Execution:** When a module needs the current time or the client IP, it calls the `ClockInterface` or `TelemetryContextInterface` resolved by the DI container.