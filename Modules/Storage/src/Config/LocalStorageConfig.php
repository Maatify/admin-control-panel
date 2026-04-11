<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

final class LocalStorageConfig
{
    public function __construct(
        public readonly string $basePath,
        public readonly string $baseUrl = '/images',
    ) {}
}
