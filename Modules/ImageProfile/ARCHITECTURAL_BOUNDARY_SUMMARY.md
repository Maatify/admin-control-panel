# ImageProfile Architectural Boundary Summary and Separation Plan

> Status note (final internal boundary pass): processing hints are now isolated as
> optional extension data (`ImageProfileProcessingExtensionDTO`) instead of being
> constructor-first canonical profile fields.

## 1. Boundary Verdict
- **Verdict:** The direction is **partially correct**. The module has a strong validation-centric core (provider + metadata reader + validator + typed DTO/value objects), but boundary drift has started through phase-9 processing concerns (variants/format/quality/processor contracts) being mixed into core entity and schema.
- **Boundary drift location:**
  - Validation core now carries fields explicitly described as processing-layer hints (`preferredFormat`, `preferredQuality`, `variants`), even when validator itself does not use them.
  - Core package also includes processing contracts and implementations under `src/Processor`.
- **Difference vs `Modules/currency` style:**
  - `currency` keeps contracts + services + PDO infra clearly split and exposes service entry points as the caller boundary.
  - `ImageProfile` has read/write contracts and PDO implementations, but lacks a similarly explicit neutral facade/service entry point for library consumers.

## 2. Keep Inside `Modules/ImageProfile`
- **Core domain (keep):**
  - `ImageProfileEntity`, value objects (`AllowedExtensionCollection`, `AllowedMimeTypeCollection`), validation enums/errors/results.
  - Read-side provider contract and implementations (`ImageProfileProviderInterface`, Array/PDO provider).
  - Metadata reader contract + native reader.
  - Validator contract + implementation.
- **General reusable read/write structures (keep):**
  - Read provider contract in `src/Contract`.
  - Write repository contract in `Application/Contract` and PDO write repository in `Infrastructure/Repository/PDO` as reusable module-internal persistence abstraction.
  - Create/update/toggle application services as reusable module services (not admin-only by naming).
- **General-purpose DTOs (keep):**
  - Validation DTOs, metadata DTOs, profile collection DTOs.
- **PDO implementations (keep):**
  - `PdoImageProfileProvider` and `PdoImageProfileRepository` are valid reusable implementations, not architectural pollution.
- **Tests/docs (keep):**
  - Contract/unit/integration tests and extraction checklist/roadmap-style docs.

## 3. Must Move Out of `Modules/ImageProfile`
- **Host-integration orchestration concerns (must live outside library package boundary):**
  - Upload lifecycle orchestration (request parsing, upload field routing, temp-file lifecycle management, controller transaction flow).
  - Storage backend orchestration and project delivery pipeline glue (validate -> store -> persist URL mapping).
  - Controller-facing integration and admin-panel shaping.
- **Already present and should be treated as outside-core project layer:**
  - `Adapter/*` and `Storage/*` are correctly outside `src/`; keep them out of library-core extraction boundary (or split to separate integration package later).

## 4. Optional But Postpone
- **Belongs to broader image ecosystem but should not be in the v1 stable core boundary yet:**
  - Variant generation contracts/DTOs/processors.
  - Resize/optimization options and output-format conversion.
  - Profile-level advisory processing hints (`preferredFormat`, `preferredQuality`).
  - Advanced profile rules tied to post-validation processing pipeline strategy.
- **Reason:** These broaden scope from “profile validation library” to “image processing subsystem,” increasing coupling and release risk.

## 5. Missing Inside the Library
- **Genuinely missing (add before extraction/move):**
  1. A clear neutral consumer-facing entry point (facade/service) that composes provider + reader + validator for normal usage, similar in spirit to how `currency` exposes services rather than repositories directly.
  2. A documented composition-root pattern for ImageProfile (DI bindings or equivalent neutral wiring reference) akin to `CurrenciesBindings`, but without host-specific assumptions.
  3. Clear boundary doc update to formally mark processing/variants as postponed or optional extension package.
  4. Naming/packaging cleanup plan for `Application/Infrastructure/Adapter/Storage` split to avoid ambiguous “what is core vs integration”.
- **Intentionally omit (do not force from `currency`):**
  - Admin pagination/filter DTO/query APIs patterned after business-admin modules.
  - Website/admin endpoint-oriented query methods.
  - Admin-specific translation-like management patterns.

## 6. Comparison with `Modules/currency`
### A. Patterns that should be mirrored
- Strong contract-driven architecture with explicit read/write boundaries.
- Service-level consumer entry points as public dependency boundary.
- Real PDO implementations behind contracts.
- Clear infrastructure-vs-business rule split.
- Explicit docs describing how consumers should depend on services/contracts.

### B. Patterns that should **not** be mirrored directly
- Admin-specific paginated list/filter APIs designed around dashboards.
- Business module assumptions (currencies/languages) that are not library-core concerns.
- Controller-first usage rules tied to app module semantics.

### C. Structural gaps
- Missing explicit library facade/query service equivalent for read validation workflows.
- Missing module-level bindings/composition guidance parallel to currency’s binding maturity.
- Scope creep from advanced processing exists inside core structures before boundary freeze.

## 7. Recommended Separation Plan
- **Phase 0 (now): freeze boundaries (no moves yet).**
  - Declare v1 core scope: profile definition + metadata read + validation + read/write profile persistence contracts + PDO examples.
  - Mark processing/variants as deferred extension scope.
- **Phase 1 (in-place refactor, still no extraction):**
  - Add neutral consumer-facing facade/service.
  - Add composition-root guidance/bindings doc.
  - Tighten docs/checklist so stable contract matches actual boundary.
- **Phase 2 (separate consumer/integration module):**
  - Move host orchestration concerns (controller flow, upload pipeline orchestration, storage lifecycle integration, admin wiring) into a dedicated consumer module/package.
- **Phase 3 (optional future package):**
  - Split processing/variants into dedicated image-processing extension package if approved.

## 8. Strict Naming Guidance
- **Use neutral library names:**
  - `ImageProfileFacade`, `ImageProfileQueryService`, `ImageProfileValidationService`, `ImageProfileRepositoryInterface`, `ImageProfileProviderInterface`.
- **Must not use Admin naming in library:**
  - Avoid `AdminImageProfileService`, `AdminImageUploadService`, `AdminImageProfileControllerDTO`.
- **General API naming:**
  - Read side: `Provider`, `Query`, `Resolver`.
  - Write side: `Repository`, `CommandService`.
  - Validation side: `Validator`, `ValidationService`.
- **Unacceptable naming for core:**
  - `UploadOrchestrator`, `StorageWorkflowManager`, `Admin*` prefixes in reusable contracts.

## 9. Final Execution Recommendation
- **Recommended next step:** **First clean/freeze library internals**, then extraction/move.
- Do **not** create a new consumer module immediately until:
  1. neutral library consumer entry point is added,
  2. composition/boundary docs are aligned,
  3. processing scope is explicitly deferred or isolated.
- **Explicit answer:** `Modules/ImageProfile` is currently missing library-consumer-facing structural maturity that `Modules/currency` implies (service/facade entry and wiring guidance). Those should be added **before** extraction/move work starts.
