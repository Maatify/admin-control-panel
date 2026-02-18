<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum ContentDocumentsErrorCodeEnum: string implements ErrorCodeInterface
{
    // ─────────────────────────────
    // NOT_FOUND
    // ─────────────────────────────
    case DOCUMENT_NOT_FOUND = 'DOCUMENT_NOT_FOUND';
    case DOCUMENT_TYPE_NOT_FOUND = 'DOCUMENT_TYPE_NOT_FOUND';
    case DOCUMENT_TRANSLATION_NOT_FOUND = 'DOCUMENT_TRANSLATION_NOT_FOUND';

    // ─────────────────────────────
    // CONFLICT
    // ─────────────────────────────
    case DOCUMENT_ACTIVATION_CONFLICT = 'DOCUMENT_ACTIVATION_CONFLICT';
    case DOCUMENT_ALREADY_ACCEPTED = 'DOCUMENT_ALREADY_ACCEPTED';
    case DOCUMENT_TRANSLATION_ALREADY_EXISTS = 'DOCUMENT_TRANSLATION_ALREADY_EXISTS';
    case DOCUMENT_TYPE_ALREADY_EXISTS = 'DOCUMENT_TYPE_ALREADY_EXISTS';
    case DOCUMENT_VERSION_ALREADY_EXISTS = 'DOCUMENT_VERSION_ALREADY_EXISTS';

    // ─────────────────────────────
    // BUSINESS_RULE
    // ─────────────────────────────
    case DOCUMENT_VERSION_IMMUTABLE = 'DOCUMENT_VERSION_IMMUTABLE';
    case INVALID_DOCUMENT_STATE = 'INVALID_DOCUMENT_STATE';

    // ─────────────────────────────
    // VALIDATION
    // ─────────────────────────────
    case INVALID_ACTOR_IDENTITY = 'INVALID_ACTOR_IDENTITY';
    case INVALID_DOCUMENT_TYPE_KEY = 'INVALID_DOCUMENT_TYPE_KEY';
    case INVALID_DOCUMENT_VERSION = 'INVALID_DOCUMENT_VERSION';

    public function getValue(): string
    {
        return $this->value;
    }
}
