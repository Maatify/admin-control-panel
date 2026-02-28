<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;

interface DocumentTranslationServiceInterface
{
    public function save(DocumentTranslationDTO $translation): void;
}
