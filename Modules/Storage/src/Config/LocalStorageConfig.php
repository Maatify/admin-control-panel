<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

final readonly class LocalStorageConfig
{
    public function __construct(
        public string $basePath,
        public string $baseUrl = '/images',
    ) {}
}
