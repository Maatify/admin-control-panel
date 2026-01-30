<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Set APP_ENV to testing for safety/tooling
// Note: This is also set in phpunit.xml, but we reinforce it here for non-PHPUnit usage if any.
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

// Disable output buffering for CI
if (function_exists('ini_set')) {
    ini_set('output_buffering', 'off');
    ini_set('implicit_flush', '1');
}
