# Open Questions

1. **PDO Connection**:
   - The module assumes a `PDO` instance is injected into the Writer. How should this connection be configured (e.g., specific charset, options) to ensure compatibility with `utf8mb4_unicode_ci` as per schema?

2. **Fallback Logging**:
   - The module uses `Psr\Log\LoggerInterface` for fallback logging. Is there a specific implementation expected, or is the generic interface sufficient?

3. **Validation Strictness**:
   - The default policy performs trimming and uppercasing for Actor Types. Is this sufficient, or should it be stricter?

4. **Query Repository Usage**:
   - The `DiagnosticsTelemetryQueryMysqlRepository` is implemented for "Readiness". How should it be exposed to the application (e.g. via a Service or directly)?
