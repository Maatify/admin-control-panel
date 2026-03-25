<?php
$content = file_get_contents(__DIR__ . '/../app/Modules/AdminKernel/Bootstrap/Container.php');
$pattern = '/(ScopesListUiController::class\s*=>\s*function\s*\(ContainerInterface\s*\$c\)\s*\{.*?)(?=\},)/s';
if (preg_match($pattern, $content, $matches)) {
    echo "ScopesListUiController:\n" . $matches[1] . "\n";
}
