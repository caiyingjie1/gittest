<?php
$app = new Eleme\Foundation\App;

$app->initErrorReporting();
$app->enableHttpMethodOverride();
$app->bindFacades();

$app['env'] = getenv('ELEME_ENV') ?: 'production';

$paths = require 'paths.php';

$app->bindInstallPaths($paths);
$app->loadEnvironment($app['path.base'], $app['env']);
$app->loadConfig($app['path.config']);

$config = $app['config']['app'];

$app->registerAlias($config['aliases']);
$app->registerExceptionHandler($config['debug']);
$app->initDefaultTimezone($config['timezone']);
$app->registerProviders($config['providers'], $config['manifest']);

$app->booted(function() use ($app) {
    $errors = $app['path'].'/errors.php';
    if (file_exists($errors)) {
        require $errors;
    }

    $path = $app['path'].'/global.php';
    if (file_exists($path)) {
        require $path;
    }

    $path = $app['path']."/{$app['env']}.php";
    if (file_exists($path)) {
        require $path;
    }

    $filters = $app['path'].'/filters.php';
    if (file_exists($filters)) {
        require $filters;
    }

    $routes = $app['path'].'/routes.php';
    if (file_exists($routes)) {
        require $routes;
    }
});

return $app;
