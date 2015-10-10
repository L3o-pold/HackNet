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

$valid_token = false;

if(!UserApp::authenticated() && isset($_COOKIE["ua_session_token"])) {
    $token = $_COOKIE["ua_session_token"];

    try{
        $valid_token = UserApp::loginWithToken($token);
    } catch(\UserApp\Exceptions\ServiceException $exception) {
        $app->flash->error($e->getMessage());
        $valid_token = false;
    }

    if ($valid_token) {
        // Set a common prefix for all routes
        $users->setPrefix('/user');

        // Use the method 'index' in PostsController
        $users->get('/', 'indexAction');
        $user->post('/', 'postAction');
        $user->put('/', 'putAction');
        $user->delete('/', 'deleteAction');

        $app->mount($users);
    }
}

/**
 * Not found handler
 */
$app->notFound(function () use ($app, $valid_token) {
    if ($valid_token) {
        $app->response->setStatusCode(404, "Not Found");
        $app->response->setJsonContent(json_encode(['errors' => [['status' => 404, 'detail' => 'Not Found']]]));
    } else {
        $app->response->setStatusCode(301, "Permission denied");
        $app->response->setJsonContent(json_encode(['errors' => [['status' => 301, 'detail' => 'Permission denied']]]));
    }

    return $app->response;
});