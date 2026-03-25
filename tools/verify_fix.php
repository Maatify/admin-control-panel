<?php
$content = file_get_contents(__DIR__ . '/../app/Modules/AdminKernel/Bootstrap/Container.php');
$pattern = '/(UiAdminsController::class => function \(ContainerInterface \$c\) \{.*?)(?=\},)/s';
if (preg_match($pattern, $content, $matches)) {
    echo "UiAdmins:\n" . $matches[1] . "\n";
}
