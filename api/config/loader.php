<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();

$loader->registerDirs(
    [
        $config->application->modelsDir,
        $config->application->pluginsDir,
        $config->application->controllersDir
    ]
)->register();

include __DIR__ . '/../vendor/autoload.php';
