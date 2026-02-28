<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysUpdateDescriptionController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysUpdateNameController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class I18nApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // i18n Root Control
        // ─────────────────────────────
        $group->group('/i18n', function (RouteCollectorProxyInterface $i18n) {

            // ─────────────────────────────
            // i18n Scope Control
            // ─────────────────────────────
            $i18n->group('/scopes', function (RouteCollectorProxyInterface $i18nScopes) {
                $i18nScopes->post(
                    '/dropdown',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesDropdownController::class,
                        '__invoke'
                    ]
                )
                    ->setName('i18n.scopes.dropdown.api');

                $i18nScopes->post(
                    '/query',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesQueryController::class,
                        '__invoke'
                    ]
                )
                    ->setName('i18n.scopes.list.api');

                $i18nScopes->post(
                    '/create',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeCreateController::class,
                        '__invoke'
                    ]
                )->setName('i18n.scopes.create.api');

                $i18nScopes->post(
                    '/change-code',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeChangeCodeController::class,
                        '__invoke'
                    ]
                )->setName('i18n.scopes.change_code.api');

                $i18nScopes->post(
                    '/set-active',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeSetActiveController::class,
                        '__invoke'
                    ]
                )->setName('i18n.scopes.set_active.api');

                $i18nScopes->post(
                    '/update-sort',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateSortController::class,
                        '__invoke'
                    ]
                )->setName('i18n.scopes.update_sort.api');

                $i18nScopes->post(
                    '/update-metadata',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateMetadataController::class,
                        '__invoke'
                    ]
                )->setName('i18n.scopes.update_metadata.api');

                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/domains/query',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsQueryController::class
                )->setName('i18n.scopes.domains.query.api');

                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/domains/assign',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainAssignController::class
                )->setName('i18n.scopes.domains.assign.api');

                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/domains/unassign',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainUnassignController::class
                )->setName('i18n.scopes.domains.unassign.api');

                $i18nScopes->get(
                    '/{scope_id:[0-9]+}/domains/dropdown',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsDropdownController::class
                )->setName('i18n.scopes.domains.dropdown.api');

                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys/query',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainKeysSummaryQueryController::class
                )->setName('i18n.scopes.domains.keys.query.api');

                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations/query',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\ScopeDomainTranslationsQueryController::class
                )->setName('i18n.scopes.domains.translations.query.api');

                $i18nScopes->get(
                    '/{scope_id:[0-9]+}/coverage',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByLanguageController::class
                )->setName('i18n.scopes.coverage.language.api');

                $i18nScopes->get(
                    '/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByDomainController::class
                )->setName('i18n.scopes.coverage.domain.api');

                // ─────────────────────────────
                // i18n Keys Control
                // ─────────────────────────────
                $i18nScopes->post(
                    '/{scope_id:[0-9]+}/keys/query',
                    \Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysQueryController::class
                )->setName('i18n.scopes.keys.query.api');

                $i18nScopes->post('/{scope_id:[0-9]+}/keys/update-name', [I18nScopeKeysUpdateNameController::class, '__invoke'])
                    ->setName('i18n.scopes.keys.update_name.api');

                $i18nScopes->post('/{scope_id:[0-9]+}/keys/create', [I18nScopeKeysCreateController::class, '__invoke'])
                    ->setName('i18n.scopes.keys.create.api');

                $i18nScopes->post('/{scope_id:[0-9]+}/keys/update_metadata', [I18nScopeKeysUpdateDescriptionController::class, '__invoke'])
                    ->setName('i18n.scopes.keys.update_metadata.api');
            });

            // ─────────────────────────────
            // i18n Domains Control
            // ─────────────────────────────
            $i18n->group('/domains', function (RouteCollectorProxyInterface $i18nDomains) {
                $i18nDomains->post(
                    '/query',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainsQueryController::class,
                        '__invoke'
                    ]
                )
                    ->setName('i18n.domains.list.api');

                $i18nDomains->post(
                    '/create',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainCreateController::class,
                        '__invoke'
                    ]
                )->setName('i18n.domains.create.api');

                $i18nDomains->post(
                    '/change-code',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainChangeCodeController::class,
                        '__invoke'
                    ]
                )->setName('i18n.domains.change_code.api');

                $i18nDomains->post(
                    '/set-active',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainSetActiveController::class,
                        '__invoke'
                    ]
                )->setName('i18n.domains.set_active.api');

                $i18nDomains->post(
                    '/update-sort',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nScopeDomainSortController::class,
                        '__invoke'
                    ]
                )->setName('i18n.domains.update_sort.api');

                $i18nDomains->post(
                    '/update-metadata',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainUpdateMetadataController::class,
                        '__invoke'
                    ]
                )->setName('i18n.domains.update_metadata.api');
            });
        });
    }
}
