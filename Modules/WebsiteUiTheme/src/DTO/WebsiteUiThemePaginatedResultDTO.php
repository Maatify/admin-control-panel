<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\DTO;

use JsonSerializable;

final class WebsiteUiThemePaginatedResultDTO implements JsonSerializable
{
    /** @param list<WebsiteUiThemeDTO> $data */
    public function __construct(
        public readonly array $data,
        public readonly PaginationDTO $pagination,
    ) {}

    /** @return array{data:list<array{id:int,entity_type:string,theme_file:string,display_name:string}>,pagination:array{page:int,per_page:int,total:int,filtered:int}} */
    public function jsonSerialize(): array
    {
        return [
            'data' => array_map(
                static fn (WebsiteUiThemeDTO $item): array => $item->jsonSerialize(),
                $this->data,
            ),
            'pagination' => $this->pagination->jsonSerialize(),
        ];
    }
}
