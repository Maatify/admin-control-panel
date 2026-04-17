<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use JsonSerializable;

/**
 * Non-blocking advisory produced by the validator.
 *
 * Warnings DO NOT flip the result to invalid. They are used for
 * low-severity remarks (e.g. "client-supplied MIME disagrees with
 * detected MIME but the detected one is allowed").
 *
 * Warning `code` values are free-form strings in Phase 1 because no
 * stable warning catalogue exists yet. When it stabilises, it will be
 * promoted to an enum — consumers are expected to treat unknown codes
 * gracefully.
 */
final readonly class ImageValidationWarningDTO implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
    ) {
    }

    /**
     * @return array{code: string, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
        ];
    }
}
