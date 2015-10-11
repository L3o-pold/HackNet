<?php
/**
 * Local variables
 *
 * @var \Phalcon\Mvc\Micro $app
 */
use Phalcon\Http\Request\Exception;
use Phalcon\Mvc\Micro\Collection as MicroCollection;
use UserApp\Widget\User as UserApp;

//$response = $app->response;
//$response->setHeader('Access-Control-Allow-Origin', '*');
//$response->setHeader('Access-Control-Allow-Headers', 'X-Requested-With');
//$response->sendHeaders();

$app->response->setContentType('application/json', 'UTF-8');

UserApp::setAppId($config->oauth->appId);

$users = new MicroCollection();
$users->setHandler(new UserController());
$users->setPrefix('/user');
$users->get('/', 'indexAction');
$users->get('/{id:[0-9]+}', 'getAction');
$users->post('/', 'postAction');
$users->put('/{id:[0-9]+}', 'putAction');
$users->delete('/{id:[0-9]+}', 'deleteAction');

//$app->options('/user', function() use ($app) {
//    $content_type = 'application/json';
//    $status = 200;
//    $description = 'OK';
//    $response = $app->response;
//
//    $status_header = 'HTTP/1.1 ' . $status . ' ' . $description;
//    $response->setRawHeader($status_header);
//    $response->setStatusCode($status, $description);
//    $response->setContentType($content_type, 'UTF-8');
//    $response->setHeader('Access-Control-Allow-Origin', '*');
//    $response->setHeader('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS');
//    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Content-Length, X-Requested-With, Access-Control-Allow-Origin');
//    $response->setHeader('Content-type: ' . $content_type, '');
//    $response->sendHeaders();
//});

$app->mount($users);

$app->notFound(function () use ($app) {
    throw new Exception('Not Found', 404);
});

$app->before(function () use ($app) {
    /**
    $router = $app->router;
    $request = $app->request;

    $route = $router->getMatchedRoute();


    if ($request->isOptions() || is_object($route) && strpos($route->getPattern(), '/preflight') !== false) {
        return true;
    }
     **/

    $authenticated = UserApp::authenticated();

    if ($authenticated) {
        return true;
    }

    if ($app->cookies->has('ua_session_token')) {
        $app->cookies->useEncryption(false);
        $token = $app->cookies->get('ua_session_token');
        $token = $token->getValue();
        $app->cookies->useEncryption(true);
    } /** elseif (isset($request->getHeaders()['Authorization'])) {
        $token = $request->getHeaders()['Authorization'];
    } **/ else {
        throw new Exception('Forbiden', 401);
    }

    return UserApp::loginWithToken($token);
});

$app->error(function ($exception) use ($app) {
    $message = 'Bad Request';
    $code    = 400;

    if ($exception instanceof Exception) {
        $message = $exception->getMessage();
        $code    = $exception->getCode();
    } else {
        $message = $exception->getMessage();
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
