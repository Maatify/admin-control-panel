<?php
/**
 * STRICT PERMISSION LINTER V2
 *
 * CI guard for the new distributed permission architecture.
 *
 * What this linter validates:
 *  - DB seed files contain canonical permissions only.
 *  - Permission maps are collected from PermissionMapProviderInterface providers across the project root and /Modules.
 *  - Duplicate route mappings across providers are forbidden.
 *  - Every protected/admin named route is either mapped by a provider or intentionally uses canonical fallback when the route name exists in any permissions_seed.sql file.
 *  - Every permission referenced by provider definitions exists in one of the discovered permissions_seed.sql files.
 *  - Provider definitions must not map routes to transport aliases; route == permission and unmapped route fallback are allowed when canonical in the DB seed.
 *  - Manual checkPermission()/requirePermission() calls must use canonical DB permissions, not route aliases.
 *
 * Usage:
 *   php scripts/dev/permission_linter.php
 *   php scripts/dev/permission_linter.php --root=/path/to/project
 *   php scripts/dev/permission_linter.php --strict-all-routes
 *
 * Exit codes:
 *   0 = no ERRORs
 *   1 = at least one ERROR
 */

declare(strict_types=1);

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;

/**
 * @param non-empty-string $name
 * @return string|null
 */
function permission_linter_arg(string $name): ?string
{
    global $argv;

    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return null;
}

/**
 * @param non-empty-string $name
 */
function permission_linter_has_flag(string $name): bool
{
    global $argv;

    return in_array('--' . $name, $argv, true);
}

/**
 * @return list<string>
 */
function permission_linter_default_exempt_routes(): array
{
    return [
        // Step-up/security flows intentionally bypass permission mapping.
        'auth.stepup.verify',
        'auth.logout.web',

        // Common public/auth names. Kept here so route discovery can be broad without noisy false positives.
        'auth.login.ui',
        'auth.login.api',
        'auth.callback.api',
        'auth.csrf.api',
        'health.api',
        'health.check.api',

        // 2FA self-service/security flows. These are not business permissions.
        '2fa.enable',
        '2fa.disable',
        '2fa.verify',
        '2fa.recovery_codes',
        '2fa.recovery_codes.regenerate',
        'auth.logout.web',
        'project.custom.view',
        'auth.logout.web',
        'me.password.view',
        'me.profile.view',
        'me.password.submit',
        'auth.logout.web',
    ];
}

/**
 * @return list<string>
 */
function permission_linter_default_excluded_dirs(): array
{
    return [
        '/.git/',
        '/vendor/',
        '/node_modules/',
        '/storage/',
        '/var/',
        '/cache/',
        '/runtime/',
        '/tmp/',
        '/tests/_output/',
    ];
}

function permission_linter_normalize_path(string $path): string
{
    return str_replace('\\', '/', $path);
}

function permission_linter_find_project_root(string $start): string
{
    $current = realpath($start) ?: $start;

    if (is_file($current)) {
        $current = dirname($current);
    }

    while ($current !== dirname($current)) {
        $hasComposer = is_file($current . DIRECTORY_SEPARATOR . 'composer.json');
        $hasDatabase = is_dir($current . DIRECTORY_SEPARATOR . 'database');
        $hasModules = is_dir($current . DIRECTORY_SEPARATOR . 'Modules');
        $hasApp = is_dir($current . DIRECTORY_SEPARATOR . 'app');

        if ($hasComposer && ($hasDatabase || $hasModules || $hasApp)) {
            return $current;
        }

        if ($hasDatabase && ($hasModules || $hasApp)) {
            return $current;
        }

        $current = dirname($current);
    }

    return realpath($start) ?: $start;
}

/**
 * @return list<string>
 */
function permission_linter_scan_roots(string $projectRoot): array
{
    $candidates = [
        $projectRoot,
        $projectRoot . DIRECTORY_SEPARATOR . 'Modules',
        $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules',
        $projectRoot . DIRECTORY_SEPARATOR . 'app',
    ];

    $roots = [];
    $seen = [];

    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real === false || !is_dir($real)) {
            continue;
        }

        $normalized = permission_linter_normalize_path($real);
        if (isset($seen[$normalized])) {
            continue;
        }

        $roots[] = $real;
        $seen[$normalized] = true;
    }

    return $roots;
}

function permission_linter_is_excluded_path(string $path): bool
{
    $normalized = permission_linter_normalize_path($path);
    foreach (permission_linter_default_excluded_dirs() as $excluded) {
        if (str_contains($normalized, $excluded)) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function permission_linter_php_files(string $root): array
{
    if (!is_dir($root)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if ($file->getExtension() !== 'php' || permission_linter_is_excluded_path($path)) {
            continue;
        }

        $files[] = $path;
    }

    sort($files);

    return $files;
}

/**
 * @param list<string> $roots
 * @return list<string>
 */
function permission_linter_php_files_in_roots(array $roots): array
{
    $files = [];
    $seen = [];

    foreach ($roots as $root) {
        foreach (permission_linter_php_files($root) as $file) {
            $real = realpath($file) ?: $file;
            if (isset($seen[$real])) {
                continue;
            }

            $files[] = $real;
            $seen[$real] = true;
        }
    }

    sort($files);

    return $files;
}

/**
 * @return list<string>
 */
function permission_linter_text_files_by_extension(string $root, string $extension): array
{
    if (!is_dir($root)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if ($file->getExtension() !== $extension || permission_linter_is_excluded_path($path)) {
            continue;
        }

        $files[] = $path;
    }

    sort($files);

    return $files;
}

/**
 * @param list<string> $candidates
 */
function permission_linter_first_existing_file(array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/**
 * @return array<string, true>
 */
function permission_linter_read_seed_permissions(string $seedFile): array
{
    if (!is_file($seedFile)) {
        return [];
    }

    $content = file_get_contents($seedFile);
    if ($content === false) {
        return [];
    }

    $permissions = [];

    // Expected seed shape usually starts values with ('permission.name', ...).
    preg_match_all("/\\(\\s*'([^']+)'\\s*,/", $content, $matches);
    foreach (($matches[1] ?? []) as $permission) {
        $permissions[$permission] = true;
    }

    ksort($permissions);

    return $permissions;
}

/**
 * Finds every permissions_seed.sql file in the workspace.
 *
 * The main AdminKernel seed is still supported, but portable modules may now own
 * their local permissions in their own permissions_seed.sql file. The linter must
 * therefore validate against the union of all discovered permission seeds.
 *
 * @param list<string> $scanRoots
 * @return list<string>
 */
function permission_linter_find_permission_seed_files(string $projectRoot, array $scanRoots): array
{
    $candidates = [
        $projectRoot . '/database/seeders/permissions_seed.sql',
        $projectRoot . '/database/seeds/permissions_seed.sql',
        $projectRoot . '/database/permissions_seed.sql',
    ];

    $files = [];
    $seen = [];

    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real === false || !is_file($real)) {
            continue;
        }

        $normalized = permission_linter_normalize_path($real);
        $files[] = $real;
        $seen[$normalized] = true;
    }

    foreach ($scanRoots as $root) {
        if (!is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if ($file->getFilename() !== 'permissions_seed.sql' || permission_linter_is_excluded_path($path)) {
                continue;
            }

            $real = realpath($path) ?: $path;
            $normalized = permission_linter_normalize_path($real);
            if (isset($seen[$normalized])) {
                continue;
            }

            $files[] = $real;
            $seen[$normalized] = true;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param list<string> $seedFiles
 * @return array{permissions:array<string, true>, sources:array<string, list<string>>}
 */
function permission_linter_read_workspace_seed_permissions(array $seedFiles): array
{
    $permissions = [];
    $sources = [];

    foreach ($seedFiles as $seedFile) {
        foreach (array_keys(permission_linter_read_seed_permissions($seedFile)) as $permission) {
            $permissions[$permission] = true;
            $sources[$permission] ??= [];
            $sources[$permission][] = $seedFile;
        }
    }

    ksort($permissions);
    ksort($sources);

    return ['permissions' => $permissions, 'sources' => $sources];
}

/**
 * @param array<string, list<string>> $seedSources
 */
function permission_linter_warn_duplicate_seed_permissions(array $seedSources, string $projectRoot, LintReport $report): void
{
    foreach ($seedSources as $permission => $sources) {
        if (count($sources) <= 1) {
            continue;
        }

        $relativeSources = array_map(
            static fn (string $source): string => str_starts_with($source, $projectRoot)
                ? ltrim(substr($source, strlen($projectRoot)), DIRECTORY_SEPARATOR)
                : $source,
            $sources
        );

        $report->warning(
            $permission,
            'Permission is declared in multiple permissions_seed.sql files: ' . implode(', ', $relativeSources)
        );
    }
}

/**
 * @return array<string, list<string>> routeName => source files
 */
/**
 * @param list<string> $scanRoots
 * @return array<string, list<string>> routeName => source files
 */
function permission_linter_scan_route_names(array $scanRoots): array
{
    $routes = [];

    foreach (permission_linter_php_files_in_roots($scanRoots) as $file) {
        $normalized = permission_linter_normalize_path($file);
        $isLikelyRouteFile = str_contains($normalized, '/Routes/')
                             || str_contains($normalized, '/routes/')
                             || str_contains($normalized, '/Route/')
                             || str_ends_with($normalized, 'routes.php')
                             || str_ends_with($normalized, 'Routes.php');

        if (!$isLikelyRouteFile) {
            continue;
        }

        $content = file_get_contents($file);
        if ($content === false || !str_contains($content, 'setName')) {
            continue;
        }

        preg_match_all('/->setName\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $content, $matches);
        foreach (($matches[1] ?? []) as $routeName) {
            $routes[$routeName] ??= [];
            $routes[$routeName][] = $file;
        }
    }

    ksort($routes);

    return $routes;
}

function permission_linter_is_admin_route_source(string $file): bool
{
    $path = permission_linter_normalize_path($file);

    return str_contains($path, '/AdminKernel/')
           || str_contains($path, '/Admin/')
           || str_contains($path, '/admin/')
           || str_contains($path, '/Modules/Admin')
           || str_contains($path, '/app/Modules/AdminKernel/');
}

/**
 * @param list<string> $sourceFiles
 */
function permission_linter_is_protected_route_candidate(string $routeName, array $sourceFiles, bool $strictAllRoutes): bool
{
    if ($strictAllRoutes) {
        return true;
    }

    foreach ($sourceFiles as $sourceFile) {
        if (permission_linter_is_admin_route_source($sourceFile)) {
            return true;
        }
    }

    // Safety net for Admin-ish naming even if the path is custom.
    return str_starts_with($routeName, 'admin.')
           || str_starts_with($routeName, 'admins.')
           || str_starts_with($routeName, 'roles.')
           || str_starts_with($routeName, 'permissions.')
           || str_starts_with($routeName, 'sessions.')
           || str_contains($routeName, '.admin.');
}

/**
 * @return class-string|null
 */
function permission_linter_fqcn_from_php_file(string $file): ?string
{
    $content = file_get_contents($file);
    if ($content === false) {
        return null;
    }

    if (!preg_match('/^\s*namespace\s+([^;]+);/m', $content, $namespaceMatch)) {
        return null;
    }

    if (!preg_match('/^\s*(?:(?:final|abstract|readonly)\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $content, $classMatch)) {
        return null;
    }

    /** @var class-string $fqcn */
    $fqcn = trim($namespaceMatch[1]) . '\\' . trim($classMatch[1]);

    return $fqcn;
}

/**
 * @return list<object>
 */
function permission_linter_discover_permission_providers(array $scanRoots): array
{
    if (!interface_exists(PermissionMapProviderInterface::class)) {
        return [];
    }

    $providers = [];
    $seen = [];

    foreach (permission_linter_php_files_in_roots($scanRoots) as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        $looksLikeProvider = str_contains($content, 'PermissionMapProviderInterface')
                             || str_contains($content, 'function permissionMap(')
                             || preg_match('/class\s+[A-Za-z_][A-Za-z0-9_]*PermissionMapProvider\b/', $content) === 1;

        if (!$looksLikeProvider) {
            continue;
        }

        $class = permission_linter_fqcn_from_php_file($file);
        if ($class === null) {
            continue;
        }

        if (!class_exists($class)) {
            require_once $file;
        }

        if (!class_exists($class) || !is_subclass_of($class, PermissionMapProviderInterface::class)) {
            continue;
        }

        if (isset($seen[$class])) {
            continue;
        }

        try {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                continue;
            }

            $constructor = $reflection->getConstructor();
            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $provider = $reflection->newInstance();
            $providers[] = $provider;
            $seen[$class] = true;
        } catch (Throwable) {
            // Provider discovery must not crash the linter because one unrelated class has side effects.
            continue;
        }
    }

    usort(
        $providers,
        static fn (object $a, object $b): int => strcmp($a::class, $b::class)
    );

    return $providers;
}

/**
 * @param list<object> $providers
 * @return array<string, object> routeName => PermissionRequirementDefinition-like object
 */
function permission_linter_collect_provider_map(array $providers, LintReport $report): array
{
    $map = [];
    $owners = [];

    foreach ($providers as $provider) {
        if (!method_exists($provider, 'permissionMap')) {
            continue;
        }

        try {
            $providerMap = $provider->permissionMap();
        } catch (Throwable $e) {
            $report->error(
                $provider::class,
                'Provider permissionMap() threw: ' . $e->getMessage()
            );
            continue;
        }

        if (!is_array($providerMap)) {
            $report->error($provider::class, 'permissionMap() must return array<string, PermissionRequirementDefinition>.');
            continue;
        }

        foreach ($providerMap as $routeName => $definition) {
            if (!is_string($routeName) || $routeName === '') {
                $report->error($provider::class, 'Provider contains a non-string or empty route key.');
                continue;
            }

            if (isset($map[$routeName])) {
                $report->error(
                    $routeName,
                    sprintf(
                        'Duplicate permission mapping provided by %s and %s.',
                        $owners[$routeName] ?? 'unknown',
                        $provider::class
                    )
                );
                continue;
            }

            if (!is_object($definition)) {
                $report->error($routeName, 'Permission map definition must be an object definition, not scalar/array.');
                continue;
            }

            $map[$routeName] = $definition;
            $owners[$routeName] = $provider::class;
        }
    }

    ksort($map);

    return $map;
}

/**
 * @return list<string>
 */
function permission_linter_definition_permissions(object $definition): array
{
    $permissions = [];

    foreach (['anyOf', 'allOf'] as $property) {
        if (!property_exists($definition, $property)) {
            continue;
        }

        /** @var mixed $value */
        $value = $definition->{$property};
        if (!is_array($value)) {
            continue;
        }

        foreach ($value as $permission) {
            if (is_string($permission) && $permission !== '') {
                $permissions[] = $permission;
            }
        }
    }

    $permissions = array_values(array_unique($permissions));
    sort($permissions);

    return $permissions;
}


/**
 * Lightweight definition used by the static provider parser.
 *
 * Runtime discovery is still preferred when autoloading works, but portable Modules may use
 * namespaces/autoload mappings that are not active in the host project during CI. Static parsing
 * keeps the linter aligned with the distributed provider architecture instead of silently missing
 * package-local providers.
 */
final readonly class LintPermissionRequirementDefinition
{
    /**
     * @param list<string> $anyOf
     * @param list<string> $allOf
     */
    public function __construct(
        public array $anyOf = [],
        public array $allOf = [],
    ) {}
}

function permission_linter_find_matching_paren(string $content, int $openPos): ?int
{
    $length = strlen($content);
    $depth = 0;
    $quote = null;
    $escape = false;

    for ($i = $openPos; $i < $length; $i++) {
        $char = $content[$i];

        if ($quote !== null) {
            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($char === '\'' || $char === '"') {
            $quote = $char;
            continue;
        }

        if ($char === '(') {
            $depth++;
            continue;
        }

        if ($char === ')') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

/**
 * @return list<string>
 */
function permission_linter_string_literals(string $content): array
{
    $values = [];

    preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $content, $matches);
    foreach (($matches[1] ?? []) as $value) {
        if (is_string($value) && $value !== '') {
            $values[] = stripcslashes($value);
        }
    }

    return array_values(array_unique($values));
}

/**
 * @return array{anyOf:list<string>, allOf:list<string>}
 */
function permission_linter_parse_compound_definition_args(string $args): array
{
    $anyOf = [];
    $allOf = [];

    if (preg_match('/anyOf\s*:\s*\[(.*?)\]/s', $args, $match) === 1) {
        $anyOf = permission_linter_string_literals($match[1]);
    }

    if (preg_match('/allOf\s*:\s*\[(.*?)\]/s', $args, $match) === 1) {
        $allOf = permission_linter_string_literals($match[1]);
    }

    // Positional/legacy compound usage is rare; do not drop it silently.
    if ($anyOf === [] && $allOf === []) {
        $anyOf = permission_linter_string_literals($args);
    }

    sort($anyOf);
    sort($allOf);

    return ['anyOf' => $anyOf, 'allOf' => $allOf];
}

/**
 * Statically extracts PermissionRequirementDefinition entries from provider files.
 *
 * @param list<string> $scanRoots
 * @return array<string, object> routeName => LintPermissionRequirementDefinition
 */
function permission_linter_scan_static_provider_maps(array $scanRoots, LintReport $report): array
{
    $map = [];
    $owners = [];

    foreach (permission_linter_php_files_in_roots($scanRoots) as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (!str_contains($content, 'permissionMap') || !str_contains($content, 'PermissionRequirementDefinition::')) {
            continue;
        }

        preg_match_all(
            '/[\'\"]([^\'\"]+)[\'\"]\s*=>\s*PermissionRequirementDefinition::(single|compound)\s*\(/',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $routeName = $match[1][0];
            $factory = $match[2][0];
            $openPos = (int) $match[0][1] + strlen($match[0][0]) - 1;
            $closePos = permission_linter_find_matching_paren($content, $openPos);

            if ($closePos === null) {
                $report->error($file, sprintf('Could not parse permission definition for route "%s".', $routeName));
                continue;
            }

            $args = substr($content, $openPos + 1, $closePos - $openPos - 1);

            if ($factory === 'single') {
                $permissions = permission_linter_string_literals($args);
                $definition = new LintPermissionRequirementDefinition(
                    anyOf: $permissions !== [] ? [$permissions[0]] : [],
                );
            } else {
                $parsed = permission_linter_parse_compound_definition_args($args);
                $definition = new LintPermissionRequirementDefinition(
                    anyOf: $parsed['anyOf'],
                    allOf: $parsed['allOf'],
                );
            }

            if (isset($map[$routeName])) {
                $existingPermissions = permission_linter_definition_permissions($map[$routeName]);
                $newPermissions = permission_linter_definition_permissions($definition);

                if ($existingPermissions !== $newPermissions) {
                    $report->error(
                        $routeName,
                        sprintf(
                            'Duplicate static permission mapping with different permissions in %s and %s.',
                            $owners[$routeName] ?? 'unknown',
                            $file
                        )
                    );
                }

                continue;
            }

            $map[$routeName] = $definition;
            $owners[$routeName] = $file;
        }
    }

    ksort($map);

    return $map;
}

/**
 * @param array<string, object> $runtimeMap
 * @param array<string, object> $staticMap
 * @return array<string, object>
 */
function permission_linter_merge_provider_maps(array $runtimeMap, array $staticMap, LintReport $report): array
{
    $merged = $runtimeMap;

    foreach ($staticMap as $routeName => $staticDefinition) {
        if (!isset($merged[$routeName])) {
            $merged[$routeName] = $staticDefinition;
            continue;
        }

        $runtimePermissions = permission_linter_definition_permissions($merged[$routeName]);
        $staticPermissions = permission_linter_definition_permissions($staticDefinition);

        if ($runtimePermissions !== $staticPermissions) {
            $report->error(
                $routeName,
                'Runtime provider mapping and static provider mapping disagree for this route.'
            );
        }
    }

    ksort($merged);

    return $merged;
}

/**
 * A transport suffix identifies route variants, not business/canonical permissions.
 *
 * Important nuance:
 *   routeName === permissionName is NOT automatically wrong.
 *   Some old canonical permissions intentionally share the same name as their route
 *   (for example: admin.email.add). That is valid when the value exists in the DB seed
 *   and does not end with an explicit transport suffix such as .api/.ui/.web/.id/.bulk.
 */
function permission_linter_has_transport_suffix(string $permission): bool
{
    return preg_match('/\.(api|ui|web|id|bulk)$/', $permission) === 1;
}

/**
 * @param array<string, true> $dbPermissions
 * @param array<string, object> $providerMap
 * @param array<string, list<string>> $routeNames
 */
function permission_linter_is_known_route_name(string $permission, array $providerMap, array $routeNames): bool
{
    return isset($providerMap[$permission]) || isset($routeNames[$permission]);
}

/**
 * Returns an error message when a permission literal is actually a route alias.
 * Returns null when the literal can continue to canonical DB permission validation.
 *
 * @param array<string, true> $dbPermissions
 * @param array<string, object> $providerMap
 * @param array<string, list<string>> $routeNames
 */
function permission_linter_route_alias_error_message(
    string $permission,
    array $dbPermissions,
    array $providerMap,
    array $routeNames
): ?string {
    $isKnownRouteName = permission_linter_is_known_route_name($permission, $providerMap, $routeNames);
    $existsInDbSeed = isset($dbPermissions[$permission]);

    if (permission_linter_has_transport_suffix($permission)) {
        return sprintf(
            'uses route/transport alias "%s" instead of canonical DB permission.',
            $permission
        );
    }

    if ($isKnownRouteName && !$existsInDbSeed) {
        return sprintf(
            'uses route name "%s", but that value is not a canonical DB permission in the seed.',
            $permission
        );
    }

    return null;
}

/**
 * @param array<string, true> $dbPermissions
 */
function permission_linter_validate_seed(array $dbPermissions, LintReport $report): void
{
    foreach (array_keys($dbPermissions) as $permission) {
        if ($permission === 'auth.logout.web') {
            continue;
        }

        if (permission_linter_has_transport_suffix($permission)) {
            $report->error($permission, 'Route/transport/variant alias found in DB seed. Seed must contain canonical permissions only.');
        }
    }
}

/**
 * @param array<string, true> $dbPermissions
 * @param array<string, object> $providerMap
 * @param array<string, list<string>> $routeNames
 */
function permission_linter_validate_provider_permissions(
    array $dbPermissions,
    array $providerMap,
    array $routeNames,
    LintReport $report
): void {
    foreach ($providerMap as $routeName => $definition) {
        $permissions = permission_linter_definition_permissions($definition);

        if ($permissions === []) {
            $report->error($routeName, 'Provider definition has no anyOf/allOf permissions.');
            continue;
        }

        foreach ($permissions as $permission) {
            $aliasError = permission_linter_route_alias_error_message($permission, $dbPermissions, $providerMap, $routeNames);
            if ($aliasError !== null) {
                $report->error(
                    $routeName,
                    'Provider ' . $aliasError
                );
                continue;
            }

            if (!isset($dbPermissions[$permission])) {
                $report->error(
                    $routeName,
                    sprintf('Provider references permission "%s" which does not exist in DB seed.', $permission)
                );
            }
        }
    }
}

/**
 * @param array<string, list<string>> $routeNames
 * @param array<string, object> $providerMap
 * @param array<string, true> $dbPermissions
 * @param list<string> $exemptRoutes
 */
function permission_linter_validate_routes_are_mapped_or_canonical(
    array $routeNames,
    array $providerMap,
    array $dbPermissions,
    array $exemptRoutes,
    bool $strictAllRoutes,
    LintReport $report
): void {
    $exempt = array_fill_keys($exemptRoutes, true);

    foreach ($routeNames as $routeName => $sourceFiles) {
        if (isset($exempt[$routeName])) {
            continue;
        }

        if (!permission_linter_is_protected_route_candidate($routeName, $sourceFiles, $strictAllRoutes)) {
            continue;
        }

        if (isset($providerMap[$routeName])) {
            continue;
        }

        // CompositePermissionMapperV2 intentionally falls back to PermissionRequirement::single($routeName).
        // Therefore an unmapped protected route is valid when the route name itself is a canonical DB permission.
        // Example: admin.email.add can be both the route name and the canonical permission.
        if (isset($dbPermissions[$routeName]) && !permission_linter_has_transport_suffix($routeName)) {
            continue;
        }

        $report->error(
            $routeName,
            'Protected/admin route is neither mapped by a PermissionMapProviderInterface provider nor present as a canonical DB permission for mapper fallback.'
        );
    }
}

/**
 * @param array<string, list<string>> $routeNames
 * @param array<string, object> $providerMap
 */
function permission_linter_validate_mapped_routes_exist(array $routeNames, array $providerMap, LintReport $report): void
{
    foreach (array_keys($providerMap) as $routeName) {
        if (!isset($routeNames[$routeName])) {
            $report->warning($routeName, 'Mapped route was not found in scanned route files.');
        }
    }
}

/**
 * @param array<string, true> $dbPermissions
 * @param array<string, object> $providerMap
 * @param array<string, list<string>> $routeNames
 */
function permission_linter_validate_unused_seed_permissions(
    array $dbPermissions,
    array $providerMap,
    array $routeNames,
    LintReport $report
): void {
    $used = [];

    foreach ($providerMap as $definition) {
        foreach (permission_linter_definition_permissions($definition) as $permission) {
            $used[$permission] = true;
        }
    }

    // Canonical fallback usage: a named route can intentionally use the same DB permission
    // without a provider entry. Count that as used so old canonical permissions do not look orphaned.
    foreach (array_keys($routeNames) as $routeName) {
        if (isset($dbPermissions[$routeName]) && !permission_linter_has_transport_suffix($routeName)) {
            $used[$routeName] = true;
        }
    }

    foreach (array_keys($dbPermissions) as $permission) {
        if (!isset($used[$permission])) {
            $report->warning($permission, 'DB permission is not referenced by any discovered permission provider or canonical route fallback.');
        }
    }
}

/**
 * @return list<array{file:string, permission:string, call:string}>
 */
function permission_linter_scan_manual_permission_calls(string $root): array
{
    $calls = [];

    foreach (permission_linter_php_files($root) as $file) {
        $normalized = permission_linter_normalize_path($file);
        $shouldScan = str_contains($normalized, '/AdminKernel/')
                      || str_contains($normalized, '/Admin/')
                      || str_contains($normalized, '/admin/');

        if (!$shouldScan) {
            continue;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (!str_contains($content, 'checkPermission') && !str_contains($content, 'requirePermission')) {
            continue;
        }

        preg_match_all('/\b(checkPermission|requirePermission)\s*\((.*?)\)\s*;/s', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $callName = $match[1];
            $arguments = $match[2];

            preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $arguments, $stringMatches);
            foreach (($stringMatches[1] ?? []) as $literal) {
                if ($literal === '' || str_contains($literal, '/') || str_contains($literal, '\\')) {
                    continue;
                }

                if (!preg_match('/^[a-z0-9_.-]+$/i', $literal)) {
                    continue;
                }

                $calls[] = [
                    'file' => $file,
                    'permission' => $literal,
                    'call' => $callName,
                ];
            }
        }
    }

    return $calls;
}

/**
 * @param array<string, true> $dbPermissions
 * @param array<string, object> $providerMap
 * @param array<string, list<string>> $routeNames
 */
function permission_linter_validate_manual_permission_calls(
    string $root,
    array $dbPermissions,
    array $providerMap,
    array $routeNames,
    LintReport $report
): void {
    foreach (permission_linter_scan_manual_permission_calls($root) as $call) {
        $permission = $call['permission'];
        $relativeFile = str_starts_with($call['file'], $root)
            ? ltrim(substr($call['file'], strlen($root)), DIRECTORY_SEPARATOR)
            : $call['file'];

        $aliasError = permission_linter_route_alias_error_message($permission, $dbPermissions, $providerMap, $routeNames);
        if ($aliasError !== null) {
            $report->error(
                $permission,
                sprintf('%s() in %s %s', $call['call'], $relativeFile, $aliasError)
            );
            continue;
        }

        if (!isset($dbPermissions[$permission])) {
            $report->error(
                $permission,
                sprintf('%s() in %s uses permission not found in DB seed.', $call['call'], $relativeFile)
            );
        }
    }
}

/**
 * @param list<object> $providers
 */
function permission_linter_run_native_provider_validator(array $providers, LintReport $report): void
{
    $validatorClass = 'Maatify\\AdminKernel\\Domain\\Security\\Permission\\PermissionMapProviderValidator';

    if (!class_exists($validatorClass)) {
        return;
    }

    try {
        $validator = new $validatorClass();
        if (method_exists($validator, 'assertNoDuplicateRoutes')) {
            $validator->assertNoDuplicateRoutes($providers);
        }
    } catch (Throwable $e) {
        $report->error('PermissionMapProviderValidator', $e->getMessage());
    }
}

final class LintReport
{
    /** @var list<array{subject:string,message:string,severity:string}> */
    private array $issues = [];

    private bool $hasErrors = false;

    public function error(string $subject, string $message): void
    {
        $this->add('ERROR', $subject, $message);
    }

    public function warning(string $subject, string $message): void
    {
        $this->add('WARNING', $subject, $message);
    }

    public function info(string $subject, string $message): void
    {
        $this->add('INFO', $subject, $message);
    }

    private function add(string $severity, string $subject, string $message): void
    {
        $this->issues[] = [
            'subject' => $subject,
            'message' => $message,
            'severity' => $severity,
        ];

        if ($severity === 'ERROR') {
            $this->hasErrors = true;
        }
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    public function print(): void
    {
        usort(
            $this->issues,
            static function (array $a, array $b): int {
                $severityOrder = ['ERROR' => 0, 'WARNING' => 1, 'INFO' => 2];

                return ($severityOrder[$a['severity']] <=> $severityOrder[$b['severity']])
                    ?: strcmp($a['subject'], $b['subject'])
                        ?: strcmp($a['message'], $b['message']);
            }
        );

        echo str_pad('SUBJECT', 64) . ' | ' . str_pad('ISSUE', 120) . ' | SEVERITY' . PHP_EOL;
        echo str_repeat('-', 64) . '-+-' . str_repeat('-', 120) . '-+----------' . PHP_EOL;

        if ($this->issues === []) {
            echo str_pad('None', 64) . ' | ' . str_pad('All checks passed', 120) . ' | INFO' . PHP_EOL;
            return;
        }

        foreach ($this->issues as $issue) {
            echo str_pad(self::shorten($issue['subject'], 64), 64)
                 . ' | '
                 . str_pad(self::shorten($issue['message'], 120), 120)
                 . ' | '
                 . $issue['severity']
                 . PHP_EOL;
        }
    }

    private static function shorten(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max - 3) . '...';
    }
}

$projectRootArg = permission_linter_arg('root');
$projectRoot = $projectRootArg !== null
    ? (realpath($projectRootArg) ?: $projectRootArg)
    : permission_linter_find_project_root(__DIR__);
$projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
$scanRoots = permission_linter_scan_roots($projectRoot);
$strictAllRoutes = permission_linter_has_flag('strict-all-routes');

$report = new LintReport();

$autoloadFile = permission_linter_first_existing_file([
    $projectRoot . '/vendor/autoload.php',
    dirname($projectRoot) . '/vendor/autoload.php',
]);

if ($autoloadFile !== null) {
    require_once $autoloadFile;
} else {
    $report->warning(
        'autoload',
        'vendor/autoload.php was not found. Runtime provider discovery was skipped; static provider parsing will be used.'
    );
}

$seedFiles = permission_linter_find_permission_seed_files($projectRoot, $scanRoots);
$seedReadResult = permission_linter_read_workspace_seed_permissions($seedFiles);
$dbPermissions = $seedReadResult['permissions'];
$seedSources = $seedReadResult['sources'];

if ($seedFiles === []) {
    $report->error('permissions_seed.sql', 'No permissions_seed.sql file was found in the project workspace.');
}

permission_linter_warn_duplicate_seed_permissions($seedSources, $projectRoot, $report);

$routeNames = permission_linter_scan_route_names($scanRoots);
$providers = permission_linter_discover_permission_providers($scanRoots);

permission_linter_run_native_provider_validator($providers, $report);

$runtimeProviderMap = permission_linter_collect_provider_map($providers, $report);
$staticProviderMap = permission_linter_scan_static_provider_maps($scanRoots, $report);
$providerMap = permission_linter_merge_provider_maps($runtimeProviderMap, $staticProviderMap, $report);

if ($providerMap === []) {
    $report->error(
        'provider_map',
        'No permission provider mappings were discovered. Runtime discovery and static provider parsing both failed.'
    );
}

permission_linter_validate_seed($dbPermissions, $report);
permission_linter_validate_provider_permissions($dbPermissions, $providerMap, $routeNames, $report);
permission_linter_validate_routes_are_mapped_or_canonical(
    $routeNames,
    $providerMap,
    $dbPermissions,
    permission_linter_default_exempt_routes(),
    $strictAllRoutes,
    $report
);
permission_linter_validate_mapped_routes_exist($routeNames, $providerMap, $report);
permission_linter_validate_manual_permission_calls($projectRoot, $dbPermissions, $providerMap, $routeNames, $report);
permission_linter_validate_unused_seed_permissions($dbPermissions, $providerMap, $routeNames, $report);

$report->info('scan_roots', 'Scanning roots: ' . implode(', ', $scanRoots));
$report->info('providers', 'Discovered runtime providers: ' . count($providers));
$report->info('static_provider_map', 'Discovered static mapped routes: ' . count($staticProviderMap));
$report->info('routes', 'Discovered named routes: ' . count($routeNames));
$report->info('provider_map', 'Discovered mapped routes: ' . count($providerMap));
$report->info('seed_files', 'Discovered permission seed files: ' . count($seedFiles));
$report->info('db_permissions', 'Discovered DB permissions: ' . count($dbPermissions));

$report->print();

exit($report->hasErrors() ? 1 : 0);
