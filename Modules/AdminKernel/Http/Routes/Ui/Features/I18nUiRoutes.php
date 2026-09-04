<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class I18nUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/i18n', function (RouteCollectorProxyInterface $i18nGroup) {

            $i18nGroup->group('/scopes', function (RouteCollectorProxyInterface $i18nScopesGroup) {

                $i18nScopesGroup->get(
                    '',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopesListUiController::class, '__invoke']
                )
                    ->setName('i18n.scopes.list.ui');

                $i18nScopesGroup->get(
                    '/{scope_id:[0-9]+}',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDetailsController::class, 'index']
                )
                    ->setName('i18n.scopes.details.ui');

                $i18nScopesGroup->get(
                    '/{scope_id:[0-9]+}/keys',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeKeysController::class, 'index']
                )
                    ->setName('i18n.scopes.keys.ui');

                $i18nScopesGroup->get(
                    '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDomainKeysSummaryController::class, 'index']
                )
                    ->setName('i18n.scopes.domains.keys.ui');

                $i18nScopesGroup->get(
                    '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDomainTranslationsUiController::class, 'index']
                )
                    ->setName('i18n.scopes.domains.translations.ui');

                $i18nScopesGroup->get(
                    '/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}',
                    \Maatify\AdminKernel\Http\Controllers\Ui\I18n\I18nScopeLanguageCoverageUiController::class
                )
                    ->setName('i18n.scopes.coverage.domain.ui');

            });

            $i18nGroup->get(
                '/domains',
                [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\DomainsListUiController::class, '__invoke']
            )
                ->setName('i18n.domains.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
