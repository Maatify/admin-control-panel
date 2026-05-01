<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Maatify\AdminKernel\Domain\Security\PermissionMapperV2;
use Maatify\AdminKernel\Domain\Security\Permission\CompositePermissionMapperV2;
use Maatify\AdminKernel\Domain\Security\Permission\KernelPermissionMapProvider;
use Maatify\AdminKernel\Domain\Security\Permission\PermissionMapProviderValidator;
use Maatify\AdminKernel\Domain\Security\Permission\SharedPermissionRequirementConverter;

$provider = new KernelPermissionMapProvider();

$providers = [$provider];

(new PermissionMapProviderValidator())->assertNoDuplicateRoutes($providers);

$oldMapper = new PermissionMapperV2();

$newMapper = new CompositePermissionMapperV2(
    providers: $providers,
    converter: new SharedPermissionRequirementConverter(),
);

$routes = array_keys($provider->permissionMap());

$routes[] = 'unknown.route.test';
$routes[] = 'auth.stepup.verify';

$failed = false;

foreach ($routes as $routeName) {
    $old = $oldMapper->resolve($routeName);
    $new = $newMapper->resolve($routeName);

    if ($old != $new) {
        $failed = true;

        echo "FAILED: {$routeName}\n";
        echo "Old:\n";
        var_dump($old);
        echo "New:\n";
        var_dump($new);
        echo "\n";
    }
}

if ($failed) {
    echo "Permission mapper smoke test failed.\n";
    exit(1);
}

echo "Permission mapper smoke test passed.\n";
echo "Checked routes: " . count($routes) . "\n";
