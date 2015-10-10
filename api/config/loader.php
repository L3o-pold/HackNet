<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();

$loader->registerDirs(
    array(
        $config->application->modelsDir
    )
)->registerNamespaces(
    array(
        'HackNet\Controllers' => $config->application->controllersDir,
    )
)->register();

include __DIR__ . '/../vendor/autoload.php';
