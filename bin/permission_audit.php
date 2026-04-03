<?php

/**
 * STRICT READ-ONLY PERMISSION AUDIT
 * Validates permission architecture rules
 */

declare(strict_types=1);

// 1. Extract DB Seed (Canonical)
$seedContent = file_get_contents(__DIR__ . '/../database/seeders/permissions_seed.sql');
preg_match_all("/\(\'([^\']+)\',/", $seedContent, $seedMatches);
$dbPermissions = $seedMatches[1] ?? [];
$dbPermissions = array_unique($dbPermissions);
sort($dbPermissions);

// 2. Extract Route Permissions
$files = shell_exec('find ' . __DIR__ . '/../app/Modules/AdminKernel/Http/Routes -type f -name "*.php"');
$filesArray = explode("\n", trim($files));
$routePermissions = [];

foreach ($filesArray as $file) {
    if (!$file) continue;
    $content = file_get_contents($file);
    // Only parse if the file uses AuthorizationGuardMiddleware or defines protected routes
    // But since setName is the key, let's extract them all:
    preg_match_all("/->setName\('([^']+)'\)/", $content, $routeMatches);
    if (!empty($routeMatches[1])) {
        foreach ($routeMatches[1] as $match) {
            $routePermissions[] = $match;
        }
    }
}
$routePermissions = array_unique($routePermissions);
sort($routePermissions);

// 3. Extract Mapper rules
$mapperContent = file_get_contents(__DIR__ . '/../app/Modules/AdminKernel/Domain/Security/PermissionMapperV2.php');
preg_match_all("/\'([^\']+)\'\s*=>\s*\'([^\']+)\'/", $mapperContent, $simpleMatches);
$mapper = [];
for ($i=0; $i<count($simpleMatches[1]); $i++) {
    $mapper[$simpleMatches[1][$i]] = $simpleMatches[2][$i];
}

preg_match_all("/\'([^\']+)\'\s*=>\s*\[\s*\'anyOf\'\s*=>\s*\[([^\]]+)\]/s", $mapperContent, $complexMatches);
for ($i=0; $i<count($complexMatches[1]); $i++) {
    $targets = [];
    preg_match_all("/\'([^\']+)\'/", $complexMatches[2][$i], $targetMatches);
    foreach ($targetMatches[1] as $target) {
        $targets[] = $target;
    }
    $mapper[$complexMatches[1][$i]] = implode(" OR ", $targets);
}

// 4. Classify & Audit
$canonical = [];
$transport = [];
$variants = [];
$issues = [];

// Determine canonicals directly from DB
foreach ($dbPermissions as $p) {
    if (preg_match('/^.+\.(api|ui|web)$/', $p)) {
        if ($p !== 'auth.logout.web') {
            $issues[] = "INVALID DB ENTRY (Transport in DB): $p";
        } else {
             $canonical[] = $p; // exception allowed
        }
    } else {
        $canonical[] = $p;
    }
}

// Check route permissions
foreach ($routePermissions as $p) {
    if (preg_match('/^.+\.(api|ui|web)$/', $p)) {
        if (isset($mapper[$p])) {
            $transport[] = $p;
        } else {
            if ($p !== 'auth.logout.web') {
               $issues[] = "UNMAPPED TRANSPORT PERMISSION: $p";
            }
        }
    } else if (isset($mapper[$p])) {
        $variants[] = $p;
    } else {
        if (!in_array($p, $dbPermissions)) {
             // Let's ensure it's not simply something not intended to be a mapped permission but is a valid route
             // Add it as an issue to be addressed or mapped correctly.
             $issues[] = "UNCLASSIFIED PERMISSION: $p";
        }
    }
}

$totalCanonical = count($canonical);
$totalTransport = count($transport);
$totalVariants = count($variants);
$totalIssues = count($issues);

// 5. Output Results

if ($totalIssues > 0) {
    echo "✔ Missing mappings checked\n";
    echo "✔ Invalid DB entries checked\n";
    echo "✔ Unclassified permissions checked\n";
    echo "\n## Issues Found\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
    echo "\n";
} else {
    echo "✔ Missing mappings checked\n";
    echo "✔ Invalid DB entries checked\n";
    echo "✔ Unclassified permissions checked\n";
    echo "\n## Issues Found\n- None\n\n";
}

echo "## Summary\n";
echo "- Total canonical: $totalCanonical\n";
echo "- Total transport: $totalTransport\n";
echo "- Total variants: $totalVariants\n";
echo "- Total issues: $totalIssues\n";
