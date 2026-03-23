<?php
/**
 * STRICT PERMISSION LINTER
 * Validates permission architecture rules for CI enforcement.
 */

declare(strict_types=1);

$projectRoot = __DIR__ . '/..';
$seedFile = $projectRoot . '/database/seeders/permissions_seed.sql';
$mapperFile = $projectRoot . '/app/Modules/AdminKernel/Domain/Security/PermissionMapperV2.php';
$routesDir = $projectRoot . '/app/Modules/AdminKernel/Http/Routes';
$controllersDir = $projectRoot . '/app/Modules/AdminKernel/Http/Controllers';
$servicesDir = $projectRoot . '/app/Modules/AdminKernel/Domain';

$issues = [];
$hasErrors = false;

function addIssue(string $permission, string $issue, string $severity): void {
    global $issues, $hasErrors;
    $issues[] = ['permission' => $permission, 'issue' => $issue, 'severity' => $severity];
    if ($severity === 'ERROR') {
        $hasErrors = true;
    }
}

// 1. Extract DB Permissions
if (!file_exists($seedFile)) {
    echo "Seed file not found. Skipping DB validation.\n";
    $dbPermissions = [];
} else {
    $seedContent = file_get_contents($seedFile);
    preg_match_all("/\(\'([^\']+)\',/", $seedContent, $matches);
    $dbPermissions = array_unique($matches[1] ?? []);
}

// 2. Extract Mapper
if (!file_exists($mapperFile)) {
    echo "Mapper file not found. Skipping Mapper validation.\n";
    $mapperKeys = [];
    $mapperMap = [];
} else {
    $mapperContent = file_get_contents($mapperFile);
    // Keys
    preg_match_all("/\'([^\']+)\'\s*=>\s*(?:\[|\')/s", $mapperContent, $mapperMatches);
    $mapperKeys = array_unique($mapperMatches[1] ?? []);

    // Simple string mappings
    preg_match_all("/\'([^\']+)\'\s*=>\s*\'([^\']+)\'/", $mapperContent, $simpleMatches);
    $mapperMap = [];
    for ($i = 0; $i < count($simpleMatches[1]); $i++) {
        $mapperMap[$simpleMatches[1][$i]] = $simpleMatches[2][$i];
    }
}

// 3. Extract Routes
$routeFiles = shell_exec("find " . escapeshellarg($routesDir) . " -type f -name '*.php' 2>/dev/null");
$routePermissions = [];
if ($routeFiles) {
    foreach (explode("\n", trim($routeFiles)) as $file) {
        if (!$file) continue;
        preg_match_all("/->setName\('([^']+)'\)/", file_get_contents($file), $routeMatches);
        foreach ($routeMatches[1] as $match) {
            $routePermissions[] = $match;
        }
    }
}
$routePermissions = array_unique($routePermissions);

// Validation 1: No transport in DB
foreach ($dbPermissions as $p) {
    if (preg_match('/^.+\.(api|ui|web)$/', $p) && $p !== 'auth.logout.web') {
        addIssue($p, 'Transport permission found in DB', 'ERROR');
    }
}

// Validation 2: No variant/transport used in API layer checkPermission
// Scan Api controllers and Services
$apiFilesStr = shell_exec("find " . escapeshellarg($controllersDir . '/Api') . " " . escapeshellarg($servicesDir) . " -type f -name '*.php' 2>/dev/null");
if ($apiFilesStr) {
    foreach (explode("\n", trim($apiFilesStr)) as $file) {
        if (!$file) continue;
        $content = file_get_contents($file);
        // Look for checkPermission('xyz')
        preg_match_all("/checkPermission\([^,]+,\s*'([^']+)'/", $content, $checkMatches);
        foreach ($checkMatches[1] as $p) {
            // Variants end in bulk/id, Transport end in api/ui/web
            if (preg_match('/^.+\.(bulk|id|api|ui|web)$/', $p)) {
                addIssue($p, 'Variant/Transport used in API checkPermission in ' . basename($file), 'ERROR');
            }
        }
    }
}

// Validation 3: All routes MUST map
$exemptRoutes = ['auth.stepup.verify', 'auth.logout.web'];
foreach ($routePermissions as $p) {
    if (in_array($p, $exemptRoutes)) continue;
    if (preg_match('/^.+\.(api|ui|web)$/', $p) && !in_array($p, $mapperKeys)) {
        addIssue($p, 'Unmapped route permission', 'ERROR');
    }
}

// Validation 4: No duplicate canonical meaning
foreach ($dbPermissions as $p) {
    if (isset($mapperMap[$p])) {
        $target = $mapperMap[$p];
        if (in_array($target, $dbPermissions) && $p !== $target) {
            addIssue($p, "Duplicate canonical meaning (resolves to $target which is also in DB)", 'ERROR');
        }
    }
}

// Validation 5: Variant justification check (soft)
$variants = [];
foreach ($mapperMap as $key => $target) {
    // A variant is a mapping that doesn't end in transport suffixes, and isn't self-referential
    if (!preg_match('/^.+\.(api|ui|web)$/', $key) && $key !== $target) {
        $variants[] = $key;
    }
}

$uiFilesStr = shell_exec("find " . escapeshellarg($controllersDir . '/Ui') . " -type f -name '*.php' 2>/dev/null");
$uiContent = '';
if ($uiFilesStr) {
    foreach (explode("\n", trim($uiFilesStr)) as $file) {
        if ($file) $uiContent .= file_get_contents($file);
    }
}

$templatesDir = $projectRoot . '/app/Modules/AdminKernel/Templates';
if (is_dir($templatesDir)) {
    $twigFiles = shell_exec("find " . escapeshellarg($templatesDir) . " -type f -name '*.twig' 2>/dev/null");
    if ($twigFiles) {
        foreach (explode("\n", trim($twigFiles)) as $file) {
            if ($file) $uiContent .= file_get_contents($file);
        }
    }
}

foreach ($variants as $v) {
    // simple check if the variant string exists anywhere in UI controllers or templates
    if (strpos($uiContent, "'$v'") === false && strpos($uiContent, "\"$v\"") === false) {
        addIssue($v, 'Variant exists but is not used in UI logic', 'WARNING');
    }
}

// Output table
echo str_pad("PERMISSION", 50) . " | " . str_pad("ISSUE", 75) . " | SEVERITY\n";
echo str_repeat("-", 50) . "-+-" . str_repeat("-", 75) . "-+----------\n";

if (empty($issues)) {
    echo str_pad("None", 50) . " | " . str_pad("All checks passed", 75) . " | INFO\n";
} else {
    foreach ($issues as $issue) {
        echo str_pad($issue['permission'], 50) . " | " . str_pad($issue['issue'], 75) . " | " . $issue['severity'] . "\n";
    }
}

if ($hasErrors) {
    exit(1);
}
exit(0);
