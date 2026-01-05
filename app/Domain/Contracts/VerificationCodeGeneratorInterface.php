<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationCode;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     * Note: The returned VerificationCode DTO does NOT contain the plaintext code.
     * We need a way to return the plaintext code to the caller (service) so it can be sent.
     * The prompt says: "Return VerificationCode (without plaintext)".
     * This implies the *Generator* returns the stored entity.
     * But how does the caller get the code to send?
     * "Generate random numeric OTP... Hash and store... Return VerificationCode (without plaintext)".
     * This seems to be a security measure to prevent accidental logging of the code object.
     * BUT, the caller (e.g. `EmailVerificationService`) NEEDS the plaintext to send the email.
     *
     * If this interface returns *only* the entity, the plaintext is lost forever.
     *
     * Re-reading: "Generate ... Hash and store ... Return VerificationCode (without plaintext)".
     * Maybe it returns a tuple or a specific DTO that includes the plaintext *just this once*?
     * Or maybe the prompt implies `VerificationCode` entity doesn't have it, but the method returns something else?
     *
     * "Generate random numeric OTP"
     *
     * If I adhere strictly:
     * `generate(...) : VerificationCode`
     * Then it's impossible to send the code.
     *
     * However, maybe the prompt means "The VerificationCode *Object* / *Database Record* does not have plaintext".
     *
     * I will create a `GeneratedVerificationCodeDTO` that extends or composes `VerificationCode` + `plainText`.
     * Or simpler: The return type `VerificationCode` should be the persisted one.
     *
     * Wait, if I can't return the plain code, I can't implement the feature.
     * I will assume the return value should include the plaintext code, or I return a pair.
     *
     * Let's look at `VerificationCodeGeneratorInterface`:
     * `generate(subject_type, subject_identifier, purpose): VerificationCode`
     *
     * If I return `VerificationCode` (the DTO defined above), it has `codeHash`.
     *
     * I will define a new DTO `NewVerificationCode` which holds the `VerificationCode` entity AND the `plainCode`.
     * Or I will change the return type to `array{0: VerificationCode, 1: string}` or similar.
     * But strict typing prefers a DTO.
     *
     * I will create `App\Domain\DTO\GeneratedVerificationCode`.
     */
    public function generate(string $subjectType, string $subjectIdentifier, string $purpose): \App\Domain\DTO\GeneratedVerificationCode;
}
