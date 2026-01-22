# Open Questions

1. **Context Construction Strategy**:
   - Currently, the `TelemetryContextDTO` is expected to be constructed by the caller (or a middleware/provider) and passed to the Recorder. Is this the intended pattern, or should the Recorder itself extract context from a global source (which might violate isolation rules)?

2. **PDO Connection**:
   - The module assumes a `PDO` instance is injected into the Writer. How should this connection be configured (e.g., specific charset, options) to ensure compatibility with `utf8mb4_unicode_ci` as per schema?

3. **Fallback Logging**:
   - The module uses `Psr\Log\LoggerInterface` for fallback logging. Is there a specific implementation expected, or is the generic interface sufficient?

4. **Archiving Implementation**:
   - The module includes `TelemetryCursorDTO` and `TelemetryReaderInterface` for "readiness". Full archiving logic (Move + Delete) is not implemented as per instructions. Is any further implementation required for "Readiness"?
