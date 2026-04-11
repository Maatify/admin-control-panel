<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

final class DOSpacesConfig
{
    public function __construct(
        public readonly string $key,
        public readonly string $secret,
        public readonly string $endpoint,
        public readonly string $bucket,
        public readonly string $region,
        public readonly string $cdnUrl,
        public readonly string $acl = 'public-read',
    ) {}
}
