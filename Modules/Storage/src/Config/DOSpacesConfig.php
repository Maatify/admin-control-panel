<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

final readonly class DOSpacesConfig
{
    public function __construct(
        public string $key,
        public string $secret,
        public string $endpoint,
        public string $bucket,
        public string $region,
        public ?string $cdnUrl,
        public string $acl = 'public-read',
    ) {}
}
