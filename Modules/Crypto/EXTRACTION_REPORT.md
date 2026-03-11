# Extraction Preparation Report: Crypto Module

## 1. Services to be Container-Bound

The following services have been registered in the DI container via `CryptoBindings`:

-   **`Maatify\Crypto\HKDF\HKDFService`**: Bound directly as it requires no constructor arguments.
-   **`Maatify\Crypto\Password\PasswordHasherInterface`**: Bound with conditional logic. It resolves to `PasswordHasher`, checking the container for an optional `PasswordPepperProviderInterface` to inject the global pepper if available.
-   **`Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmInterface`**: Bound to the concrete implementation `SodiumAeadXchacha20poly1305Ietf`, enforcing strong defaults.
-   **`Maatify\Crypto\DX\CryptoContextFactory`**: Bound, requiring `KeyRotationService`, `HKDFService`, and `ReversibleCryptoAlgorithmInterface`.
-   **`Maatify\Crypto\DX\CryptoDirectFactory`**: Bound, requiring `KeyRotationService` and `ReversibleCryptoAlgorithmInterface`.
-   **`Maatify\Crypto\DX\CryptoProvider`**: Bound as the primary facade, requiring `CryptoContextFactory` and `CryptoDirectFactory`.

*Note:* `KeyRotationService` is required by factories but is *not* explicitly bound in `CryptoBindings`. This is intentional because it depends on `KeyProviderInterface` and `KeyRotationPolicyInterface`, which MUST be provided by the host application.

## 2. Classes Remaining Internal

-   Internal DTOs (`CryptoKeyDTO`, etc.) do not need container bindings as they are passed as data objects.
-   Specific internal algorithms (e.g., `SodiumAeadXchacha20poly1305Ietf`) are bound via their interface but generally shouldn't be overridden by the host application unless strictly necessary and reviewed.
-   Enum classes (`KeyStatusEnum`, `ReversibleCryptoAlgorithmEnum`) are structural and not service-oriented.

## 3. Requirements for Extraction

To extract this module into a standalone Composer package (`maatify/crypto`):

1.  **Extract Directory:** Move the `Modules/Crypto` directory to its own repository.
2.  **Composer File:** Create a `composer.json` defining the package name (`maatify/crypto`), autoloader rules (PSR-4 for `Maatify\Crypto\`), and dependencies. It likely needs `ext-sodium` and PHP >= 8.1.
3.  **Update Application Usage:** Update the main application's `composer.json` to require the new package.
4.  **Register Bindings:** The host application must call `\Maatify\Crypto\Bootstrap\CryptoBindings::register($containerBuilder)` during its DI setup phase and provide implementations for mandatory interfaces like `KeyProviderInterface`.

## 4. Security Considerations

-   **Host Responsibilities:** The module is secure by design, but its actual security relies entirely on the host application securely managing and injecting Root Keys and Password Peppers.
-   **Fail-Closed Validation:** The current implementation strictly enforces AEAD and fail-closed behaviors. Ensure no future PRs introduce "fallback" mechanisms or weaken algorithm requirements.
-   **Dependencies:** The module relies heavily on the `ext-sodium` extension for `XChaCha20-Poly1305`. This must be a strict requirement in the standalone package's `composer.json`.
