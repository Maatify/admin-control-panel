<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Contract;

use Maatify\LanguageCore\DTO\LanguageCollectionDTO;
use Maatify\LanguageCore\DTO\LanguageDTO;

interface LanguageContextQueryInterface
{
    /**
     * Returns one language with optional settings context (icon, direction).
     * Uses LEFT JOIN semantics at implementation level.
     */
    public function getByIdWithContext(int $id): ?LanguageDTO;

    /**
     * Returns one language by code with optional settings context (icon, direction).
     * Uses LEFT JOIN semantics at implementation level.
     */
    public function getByCodeWithContext(string $code): ?LanguageDTO;

    /**
     * Returns all languages with optional settings context (icon, direction).
     */
    public function listAllWithContext(): LanguageCollectionDTO;

    /**
     * Returns active languages with optional settings context (icon, direction).
     */
    public function listActiveWithContext(): LanguageCollectionDTO;
}
