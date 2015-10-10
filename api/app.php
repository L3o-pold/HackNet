<?php
/**
 * Local variables
 *
 * @var \Phalcon\Mvc\Micro $app
 */

use Phalcon\Mvc\Micro\Collection as MicroCollection;
use \UserApp\Widget\User as UserApp;
use HackNet\Controllers\UserController as UserController;


$app->response->setContentType('application/json', 'UTF-8');

UserApp::setAppId("56191c36bd950");

$users = new MicroCollection();

// Set the main handler. ie. a controller instance
$users->setHandler(new UserController());

// Set a common prefix for all routes
$users->setPrefix('/user');

// Use the method 'index' in PostsController
$users->get('/', 'indexAction');
$users->post('/', 'postAction');
$users->put('/', 'putAction');
$users->delete('/', 'deleteAction');
$app->mount($users);

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found");
    $app->response->setJsonContent(json_encode(['errors' => [['status' => 404, 'detail' => 'Not Found']]]));
    return $app->response;
});