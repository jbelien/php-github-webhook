<?php

declare(strict_types=1);

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
(function () {
    $config = require 'config/config.php';

    $dependencies = $config['dependencies'];
    $dependencies['services']['config'] = $config;

    $container = new ServiceManager($dependencies);

    $app = $container->get(Application::class);
    $factory = $container->get(MiddlewareFactory::class);

    (require 'config/pipeline.php')($app, $factory, $container);
    (require 'config/routes.php')($app, $factory, $container);

    $app->run();
})();
