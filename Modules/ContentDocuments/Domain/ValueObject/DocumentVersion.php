<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentVersionException;

final readonly class DocumentVersion
{
    private const MAX_LENGTH = 32;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidDocumentVersionException('Document version must not be empty.');
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDocumentVersionException('Document version exceeds max length of ' . self::MAX_LENGTH . '.');
        }

        $this->value = $value;
    }

    public function equals(DocumentVersion $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
