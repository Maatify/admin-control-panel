# Canonical Architecture: BehaviorTrace

## Layering

```
[Application / Host]
       |
       v
[Recorder (Policy Boundary)] <--- BehaviorTraceRecorder
       |
       v
[Contract (Interfaces)] <--- BehaviorTraceLoggerInterface
       |
       v
[Infrastructure (Storage)] <--- BehaviorTraceLoggerMysqlRepository
       |
       v
[MySQL Table: operational_activity]
```

## Design Decisions

1.  **Isolation:** Module is self-contained. No external domain logic.
2.  **Swallowing:** `BehaviorTraceRecorder` swallows `BehaviorTraceStorageException` to preserve "Fail-Open" semantics.
3.  **Strict Typing:** DTOs are used for all internal data passing.
4.  **Polymorphism:** `ActorType` can be an Enum or a String (normalized by Policy).
5.  **Validation:** `BehaviorTraceDefaultPolicy` handles normalization and strict limits.

## Database Schema

*   **Table:** `operational_activity`
*   **Key Index:** `(occurred_at, id)` for stable cursors.
*   **Search Indexes:** `action`, `actor`, `correlation`.

## Future Extraction

This module is designed to be extracted to `maatify/behavior-trace` package.
It depends only on standard PHP extensions and PSR Interfaces (Log).
