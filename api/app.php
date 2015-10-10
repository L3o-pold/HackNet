<?php
/**
 * Local variables
 *
 * @var \Phalcon\Mvc\Micro $app
 */
use Phalcon\Http\Request\Exception;
use Phalcon\Mvc\Micro\Collection as MicroCollection;
use UserApp\Widget\User as UserApp;

$app->response->setContentType('application/json', 'UTF-8');

UserApp::setAppId($config->oauth->appId);

/**
 * @todo move to route
 */
$users = new MicroCollection();
$users->setHandler(new UserController());
$users->setPrefix('/user');
$users->get('/', 'indexAction');
$users->get('/{id:[0-9]+}', 'getAction');
$users->post('/', 'postAction');
$users->put('/{id:[0-9]+}', 'putAction');
$users->delete('/{id:[0-9]+}', 'deleteAction');

$app->mount($users);

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    throw new Phalcon\Http\Request\Exception('Not Found', 404);
});

// Executed before every route is executed
// Return false cancels the route execution
$app->before(function () use ($app) {
    $valid_token   = false;
    $authenticated = UserApp::authenticated();
    if (!$authenticated && $app->cookies->has('ua_session_token')) {

        $token = $app->cookies->get('ua_session_token');

        $token = $token->getValue();

        try {
            $valid_token = UserApp::loginWithToken($token);
        } catch (\UserApp\Exceptions\ServiceException $exception) {
            throw new Exception('Forbidden: ' . $exception->getMessage(), 403);
        }
    }

    if (!$authenticated && !$valid_token) {
        throw new Exception('Forbidden', 403);
    }

    return true;
});

$app->error(function ($exception) use ($app) {
    $message = 'Bad Request';
    $code    = 400;

    if ($exception instanceof Exception) {
        $message = $exception->getMessage();
        $code    = $exception->getCode();
    }

    $app->response->setStatusCode($code, $message);

    $app->response->setJsonContent([
        'errors' => [
            [
                'status' => 'ERROR',
                'messages' => [$message]
            ]
        ]
    ]);
    $app->response->send();
});
