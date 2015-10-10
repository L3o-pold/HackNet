<?php
/**
 * Local variables
 *
 * @var \Phalcon\Mvc\Micro $app
 */

include __DIR__ . '/vendor/autoload.php';

use Phalcon\Mvc\Micro\Collection as MicroCollection;
use HackNet\Controllers\UserController as UserController;

$users = new MicroCollection();

// Set the main handler. ie. a controller instance
$users->setHandler(new UserController());

// Set a common prefix for all routes
$users->setPrefix('/user');

// Use the method 'index' in PostsController
$users->get('/', 'indexAction');
$users->get('/login', 'loginAction');
$users->get('/signout', 'signoutAction');
$users->get('/loginWithGoogle', 'loginWithGoogleAction');
$users->get('/register', 'registerAction');
$users->post('/register', 'registerAction');

$app->mount($users);

/**
 * Add your routes here
 */
$app->get('/', function () use ($app) {
    echo $app['view']->render('index');
});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo $app['view']->render('404');
});