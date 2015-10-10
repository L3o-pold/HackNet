<?php
/**
 * Services are globally registered in this file
 *
 * @var \Phalcon\Config $config
 */

use Phalcon\Mvc\View\Simple as View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

$di = new FactoryDefault();

/**
 * Sets the view component
 */
$di->setShared('view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    return $view;
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);
    return $url;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () use ($config) {
    $dbConfig = $config->database->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});

//Make config settings available
$di->set('config', $config, true);

/**
 * Plugging the PhalconUserPlugin
 */
$di['dispatcher'] = function() use ($di) {
    $eventsManager = $di->getShared('eventsManager');
    $security = new \Phalcon\UserPlugin\Plugin\Security($di);
    $eventsManager->attach('dispatch', $security);
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('HackNet\Controllers');
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
};

/**
 * Register Auth, ACL and Mail services used by PhalconUserPlugin
 */
$di['auth'] = function(){
    return new \Phalcon\UserPlugin\Auth\Auth();
};
$di['acl'] = function() {
    return new \Phalcon\UserPlugin\Acl\Acl();
};
$di['mail'] = function() {
    return new \Phalcon\UserPlugin\Mail\Mail();
};