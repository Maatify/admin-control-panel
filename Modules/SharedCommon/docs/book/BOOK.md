# SharedCommon Module Documentation

Welcome to the comprehensive documentation book for the `Maatify\SharedCommon` module.

This book provides in-depth explanations of the foundational contracts, architecture, design decisions, and integration patterns required for a unified cross-module infrastructure.

## Table of Contents

* **[Chapter 1: Overview](01_overview.md)**
  The philosophy, purpose, and significance behind SharedCommon.

* **[Chapter 2: Architecture](02_architecture.md)**
  Detailed explanation of the layer boundaries (Contracts, Infrastructure, Bootstrap) and responsibilities.

* **[Chapter 3: Domain Objects](03_domain_objects.md)**
  In-depth look at the core interfaces representing time, telemetry, and system state contexts.

* **[Chapter 4: Clock Abstraction](04_clock_abstraction.md)**
  Why the `ClockInterface` is crucial for predictable time tracking, testability, and consistency.

* **[Chapter 5: Integration Patterns](05_integration_patterns.md)**
  Real-world DI container wiring and cross-module usage across applications.

* **[Chapter 6: Extension Points](06_extension_points.md)**
  Techniques for extending the module by providing application-specific infrastructure implementations for telemetry and security contexts.
