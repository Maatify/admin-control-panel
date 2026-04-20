<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\Infrastructure\Repository\PdoWebsiteUiThemeQueryReader;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeFacade;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeQueryService;
use PDO;
use Psr\Container\ContainerInterface;

final class WebsiteUiThemeBindings
{
    /** @param ContainerBuilder<Container> $builder */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            WebsiteUiThemeQueryReaderInterface::class => static function (ContainerInterface $c): PdoWebsiteUiThemeQueryReader {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoWebsiteUiThemeQueryReader($pdo);
            },

            WebsiteUiThemeQueryService::class => static function (ContainerInterface $c): WebsiteUiThemeQueryService {
                /** @var WebsiteUiThemeQueryReaderInterface $reader */
                $reader = $c->get(WebsiteUiThemeQueryReaderInterface::class);

                return new WebsiteUiThemeQueryService($reader);
            },

            WebsiteUiThemeFacade::class => static function (ContainerInterface $c): WebsiteUiThemeFacade {
                /** @var WebsiteUiThemeQueryService $queryService */
                $queryService = $c->get(WebsiteUiThemeQueryService::class);

                return new WebsiteUiThemeFacade($queryService);
            },
        ]);
    }
}
