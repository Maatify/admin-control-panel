<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentTypeKeyException;

final readonly class DocumentTypeKey
{
    private const MAX_LENGTH = 64;
    private const PATTERN = '/^[a-z0-9\-]+$/';

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidDocumentTypeKeyException('Document type key must not be empty.');
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDocumentTypeKeyException('Document type key exceeds max length of ' . self::MAX_LENGTH . '.');
        }

        if ($value !== strtolower($value)) {
            throw new InvalidDocumentTypeKeyException('Document type key must be lowercase.');
        }

        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidDocumentTypeKeyException('Document type key must match pattern: ' . self::PATTERN);
        }

        $this->value = $value; // ← assignment happens exactly once
    }

    public function equals(DocumentTypeKey $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
