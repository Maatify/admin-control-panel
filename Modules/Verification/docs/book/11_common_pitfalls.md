# Chapter 11: Common Pitfalls

When integrating the `Maatify\Verification` module, developers should avoid the following common mistakes to ensure the security and integrity of the verification process.

## 1. Storing Plain Text Codes

**The Pitfall:** Extracting the `plainCode` from the `GeneratedVerificationCode` DTO and saving it in a local database column (e.g., `users.email_verification_code = '123456'`) alongside the user record.

**Why it's dangerous:** This entirely defeats the primary security mechanism of the module. The `VerificationCode` entity only stores a `sha256` hash. Storing the plain text code exposes your users to significant risk if your database is compromised.

**The Solution:** Only use the `plainCode` immediately after generation to transmit it to the user (e.g., via Email/SMS). Rely entirely on the `VerificationCodeValidatorInterface` to verify submissions.

## 2. Implementing Custom Failure Tracking

**The Pitfall:** Building custom logic in your application controllers to count how many times a user has guessed a code incorrectly, or creating a separate `failed_attempts` table.

**Why it's redundant and risky:** The `VerificationCodeValidator` already has built-in, secure anti-brute-force tracking. It atomically increments the `attempts` column in the repository and permanently expires the code when `maxAttempts` is reached. Implementing custom tracking is not only redundant but often introduces race conditions that attackers can exploit.

**The Solution:** Trust the validator's boolean `success` response. If it returns false, the module has already securely handled the failure recording and potential lockout.

## 3. Leaking Validation Failure Reasons

**The Pitfall:** Differentiating failure messages to the user. For example, telling the user "Code expired" vs "Incorrect code" vs "Max attempts reached."

**Why it's dangerous:** This provides a side-channel for attackers. If an attacker knows a code exists but is incorrect, they can continue guessing. If they know a code doesn't exist at all, they can scan for valid accounts.

**The Solution:** The `VerificationResult` intentionally returns a generic `Invalid code.` reason. Your application should bubble up a similarly generic error message (e.g., "The verification code is invalid or has expired.") regardless of the specific underlying reason.

## 4. Bypassing the Generator for "Test Codes"

**The Pitfall:** Manually inserting rows into the `verification_codes` table during testing or seeding, or attempting to instantiate `VerificationCode` DTOs directly without using the generator.

**Why it's problematic:** The `VerificationCodeGenerator` enforces critical lifecycle rules, specifically invalidating any *previously active* codes for that user and purpose. Bypassing the generator means you might accidentally create a scenario where multiple active codes exist simultaneously, violating the domain's guarantees.

**The Solution:** Always use the `VerificationCodeGeneratorInterface->generate()` method, even in test environments or database seeders.

## 5. Ignoring IP Auditing

**The Pitfall:** Always passing `null` for the `$createdIp` and `$usedIp` parameters in the generation and validation methods.

**Why it's a missed opportunity:** While optional, tracking the source IPs is crucial for identifying systemic brute-force attacks or compromised accounts across your platform.

**The Solution:** Make a best effort to retrieve the client's IP address from your framework's request object and pass it to the verification module.

## 6. Using String Magic Instead of Enums

**The Pitfall:** Trying to pass string literals like `'email'` or `'email_verification'` directly into the domain services.

**Why it fails:** The domain strictly types its arguments using `IdentityTypeEnum` and `VerificationPurposeEnum`.

**The Solution:** Always use the defined enum cases (e.g., `IdentityTypeEnum::Email`, `VerificationPurposeEnum::EmailVerification`). If you need new types, read the Extension Points chapter.