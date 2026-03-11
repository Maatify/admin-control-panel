# Maatify SharedCommon

## Overview

The `Maatify\SharedCommon` module contains foundational contracts and abstractions that are intended to be shared across all Maatify modules (such as `AdminKernel`, `Verification`, etc.). Its primary goal is to provide unified interfaces for common cross-cutting concerns like time management, security contexts, and telemetry, enabling consistent behavior and testing across the entire system.

## Purpose

By depending on `SharedCommon` rather than framework-specific implementations or raw PHP functions (like `time()` or `date()`), other modules can remain truly framework-agnostic. This module defines the "how we communicate" contracts for basic system realities.

## Module Structure

```
Modules/SharedCommon/
├── Bootstrap/                 # Dependency Injection bindings
│   └── SharedCommonBindings.php
├── Contracts/                 # Core interfaces for time, telemetry, and security
│   ├── ClockInterface.php
│   ├── SecurityEventContextInterface.php
│   └── TelemetryContextInterface.php
├── Infrastructure/            # Default implementations of contracts
│   └── SystemClock.php
├── docs/                      # Architectural and integration documentation
└── composer.json              # Standalone package metadata
```

## Quick Usage

To quickly integrate the default implementations of this module into your dependency injection container:

```php
use Maatify\SharedCommon\Bootstrap\SharedCommonBindings;
use DI\ContainerBuilder;
use Maatify\SharedCommon\Contracts\ClockInterface;

$builder = new ContainerBuilder();

// Register the bindings for SharedCommon contracts
SharedCommonBindings::register($builder);

$container = $builder->build();

// Resolve the Clock
/** @var ClockInterface $clock */
$clock = $container->get(ClockInterface::class);

// Get the current time as a DateTimeImmutable object
$now = $clock->now();
echo $now->format('Y-m-d H:i:s');
```

## Further Documentation

- [How to Use](HOW_TO_USE.md) - Practical integration instructions.
- [Changelog](CHANGELOG.md) - History and evolution of the module.

### Documentation Book

Comprehensive architecture and integration guides are available in the Book:

| Chapter | Description |
|---|---|
| [Table of Contents](docs/book/BOOK.md) | Main entry point for the documentation book. |
| [01. Overview](docs/book/01_overview.md) | The philosophy and purpose behind SharedCommon. |
| [02. Architecture](docs/book/02_architecture.md) | Layering and separation of interfaces and infrastructure. |
| [03. Domain Objects](docs/book/03_domain_objects.md) | Core contracts representing the system state context. |
| [04. Clock Abstraction](docs/book/04_clock_abstraction.md) | Why the ClockInterface is crucial for testability and consistency. |
| [05. Integration Patterns](docs/book/05_integration_patterns.md) | Real-world DI container wiring and cross-module usage. |
| [06. Extension Points](docs/book/06_extension_points.md) | How to provide application-specific context implementations. |
