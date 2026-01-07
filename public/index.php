<?php

declare(strict_types=1);

use App\Bootstrap\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Create Container (This handles ENV loading and AdminConfigDTO)
$container = Container::create();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register Routes
$routes = require __DIR__ . '/../routes/web.php';
$routes($app);

// Run App
$app->run();
