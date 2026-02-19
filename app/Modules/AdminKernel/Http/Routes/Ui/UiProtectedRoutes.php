<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui;

use Maatify\AdminKernel\Http\Controllers\Ui\LanguagesListController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class UiProtectedRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('', function (RouteCollectorProxyInterface $protectedGroup) {
            $protectedGroup->get('/', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);
            $protectedGroup->get('/dashboard', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);

            // ─────────────────────────────
            // 2FA Setup (Enrollment)
            // ─────────────────────────────

            $protectedGroup->get(
                '/2fa/setup',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, 'index']
            )
                ->setName('2fa.setup');

            $protectedGroup->post(
                '/2fa/setup',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, 'enable']
            )
                ->setName('2fa.enable');

            // ─────────────────────────────
            // Content Documents Control
            // ─────────────────────────────

            $protectedGroup->get(
                '/content-document-types',
                [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiDocumentTypesController::class, 'index']
            )
                ->setName('content_documents.types.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/content-document-types/{type_id:[0-9]+}/documents',
                [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiDocumentVersionsController::class, 'index']
            )
                ->setName('content_documents.versions.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/translations',
                [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiDocumentTranslationsController::class, 'index']
            )
                ->setName('content_documents.translations.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/acceptance',
                [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiDocumentAcceptanceController::class, 'index']
            )
                ->setName('content_documents.acceptance.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/admins',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'index']
            )
                ->setName('admins.list.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/admins/create',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminCreateController::class, 'index']
            )
                ->setName('admin.create.ui')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // Admin Profile (VIEW)
            // ===============================
            $protectedGroup->get(
                '/admins/{id:[0-9]+}/profile',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'profile']
            )
                ->setName('admins.profile.view')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // Admin Profile (EDIT FORM)
            // ===============================
            $protectedGroup->get(
                '/admins/{id:[0-9]+}/profile/edit',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'editProfile']
            )
                ->setName('admins.profile.edit.view')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // Admin by ID
            // ===============================
            $protectedGroup->group('/admins', function (RouteCollectorProxyInterface $adminsGroup) {
                // ===============================
                // Admin Profile (UPDATE)
                // ===============================
                $adminsGroup->post(
                    '/{id:[0-9]+}/profile/edit',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'updateProfile']
                )
                    ->setName('admins.profile.edit');

                // ─────────────────────────────
                // Admin Email Control
                // ─────────────────────────────
                $adminsGroup->get(
                    '/{id:[0-9]+}/emails',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'emails']
                )
                    ->setName('admin.email.list.ui');

                // ─────────────────────────────
                // Admin Session Control
                // ─────────────────────────────
                $adminsGroup->get(
                    '/{id:[0-9]+}/sessions',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'sessions']
                )
                    ->setName('admins.session.list');

                // ─────────────────────────────
                // Admin Permissions Control
                // ─────────────────────────────
                $adminsGroup->get(
                    '/{id:[0-9]+}/permissions',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'permissions']
                )
                    ->setName('admins.permissions');
            })
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // i18n
            // ===============================
            $protectedGroup->group('/i18n', function (RouteCollectorProxyInterface $i18nGroup) {

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

            })
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Permission By ID Control
            // ─────────────────────────────
            $protectedGroup->get(
                '/permissions/{permission_id:[0-9]+}',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiAPermissionDetailsController::class, 'index']
            )
                ->setName('permission.details.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/roles',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRolesController::class, 'index']
            )
                ->setName('roles.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/roles/{id:[0-9]+}',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRoleDetailsController::class, '__invoke']
            )
                ->setName('roles.view.ui')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/permissions',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiPermissionsController::class, 'index']
            )
                ->setName('permissions.query.ui')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // languages
            // ===============================
            $protectedGroup->group('/languages', function (RouteCollectorProxyInterface $languagesGroup) {
                $languagesGroup->get('', [LanguagesListController::class, '__invoke'])
                    ->setName('languages.list.ui');

                $languagesGroup->get(
                    '/{language_id:[0-9]+}/translations',
                    [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\LanguageTranslationsListUiController::class, '__invoke']
                )
                    ->setName('languages.translations.list.ui');
            })
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get(
                '/app-settings',
                [
                    \Maatify\AdminKernel\Http\Controllers\Ui\AppSettings\AppSettingsListUiController::class,
                    '__invoke'
                ]
            )->setName('app_settings.list.ui');

            $protectedGroup->get('/settings', [\Maatify\AdminKernel\Http\Controllers\Ui\UiSettingsController::class, 'index']);

            // UI sandbox for Twig/layout experimentation (non-canonical page)
            $protectedGroup->get('/examples', [\Maatify\AdminKernel\Http\Controllers\Ui\UiExamplesController::class, 'index']);

            // Phase 14.3: Sessions LIST
            $protectedGroup->get(
                '/sessions',
                [\Maatify\AdminKernel\Http\Controllers\Ui\SessionListController::class, '__invoke']
            )
                ->setName('sessions.list.ui')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Activity Logs
            // ─────────────────────────────

            $protectedGroup->get('/activity-logs', [\Maatify\AdminKernel\Http\Controllers\Ui\ActivityLogListController::class, 'index'])
                ->setName('activity_logs.view');

            $protectedGroup->get('/telemetry', [\Maatify\AdminKernel\Http\Controllers\Ui\TelemetryListController::class, 'index'])
                ->setName('telemetry.list');

            // Allow logout from UI
            $protectedGroup->post('/logout', [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout'])
                ->setName('auth.logout');
            $protectedGroup->get('/logout', [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout'])
                ->setName('auth.logout.web');
        })
            // NOTE [Slim Middleware Order]:
            // Slim executes middlewares in LIFO order (last added = first executed).
            // This ordering is intentional so AdminContextMiddleware runs
            // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
            // consume AdminContext and expose `current_admin` as a global.
            ->add(\Maatify\AdminKernel\Http\Middleware\TwigAdminContextMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);
        //                ->add(\Maatify\AdminKernel\Http\Middleware\RememberMeMiddleware::class);
    }
}
