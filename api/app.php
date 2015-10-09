<?php
/**
 * Local variables
 *
 * @var \Phalcon\Mvc\Micro $app
 */

include __DIR__ . '/vendor/autoload.php';

/**
 * Add your routes here
 */
$app->get('/', function () use ($app) {
    echo $app['view']->render('index');
});

$app->get("/access_token", function () use ($app) {
    try {
        $response = $app->oauth->authorize->issueAccessToken();
        $app->oauth->setData($response);
    } catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

$app->get('/authorize', function () use ($app) {
    /** @var \League\OAuth2\Server\Grant\AuthCodeGrant $codeGrant */
    $authParams = null;
    try {
        $codeGrant  = $app->oauth->authorize->getGrantType('authorization_code');
        $authParams = $codeGrant->checkAuthorizeParams();
    } catch (\Exception $e) {
        return $app->oauth->catcher($e);
    }
    if ($authParams) {
        $redirect = $codeGrant->newAuthorizeRequest('client', "testclient", $authParams);
        $response = new \Phalcon\Http\Response();
        //return $response->redirect("signin", false, 302)->sendHeaders();
        return $response->redirect($redirect, true, 302)->sendHeaders();
    } else {
        $error = new \League\OAuth2\Server\Util\AccessDeniedException;
        return $app->oauth->catcher($error);
    }
});

$app->get('/signin', function () use ($app) {
    echo $app['view']->render('signup');
});

$app->post('/signin', function() use ($app) {
    if (!isset($_POST['authorization'])) {
        echo $app['view']->render('signup');
    } elseif ($_POST['authorization'] === 'Approve') {
        $codeGrant  = $app->oauth->authorize->getGrantType('authorization_code');
        $authParams = $codeGrant->checkAuthorizeParams();
        //$redirectUri = $app->oauth->getGrantType('authorization_code')->newAuthorizeRequest('user', 1, $authParams);
        $redirectUri = $codeGrant->newAuthorizeRequest('client', "testclient", $authParams);
        $response = new \Phalcon\Http\Response();
        return $response->redirect($redirectUri, true, 302)->sendHeaders();
    }
    // The user denied the request so redirect back with a message
    else {
        $error = new \League\OAuth2\Server\Util\AccessDeniedException;
        return $app->oauth->catcher($error);
    }
});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo $app['view']->render('404');
});

$app->after(function () use ($app) {
    $returned = $app->getReturnedValue();
    $app->response->sendHeaders();
    if ($returned) {
        if(is_scalar($returned))
            echo $returned;
        else
            $app->oauth->setData($returned);
    }
    $app->response->send();
});
$app->finish(function () use ($app) {
    $app->oauth->cleanData();
});
