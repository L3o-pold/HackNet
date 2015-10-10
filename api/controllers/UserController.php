<?php


namespace HackNet\Controllers;

use Phalcon\Mvc\Controller;

use Phalcon\UserPlugin\Models\User\User;
use Phalcon\UserPlugin\Models\User\UserResetPasswords;
use Phalcon\UserPlugin\Models\User\UserPasswordChanges;

use Phalcon\UserPlugin\Forms\User\LoginForm;
use Phalcon\UserPlugin\Forms\User\RegisterForm;
use Phalcon\UserPlugin\Forms\User\ForgotPasswordForm;
use Phalcon\UserPlugin\Forms\User\ChangePasswordForm;

use Phalcon\Http\Response;

use Phalcon\Mvc\View;
use Phalcon\Tag;

use \UserApp\Widget\User as UserApp;

class UserController extends Controller
{

    public function indexAction() {

        $this->response->setStatusCode(301, "Permission denied");
        $this->response->setJsonContent(json_encode(['errors' => [['status' => 301, 'detail' => 'Permission denied']]]));

        if(!UserApp::authenticated() && isset($_COOKIE["ua_session_token"])) {
            $token = $_COOKIE["ua_session_token"];

            try {
                $valid_token = UserApp::loginWithToken($token);
            } catch(\UserApp\Exceptions\ServiceException $exception) {
                $this->flash->error($exception->getMessage());
                $valid_token = false;
            }

            if (!$valid_token) {

            } else {
                $this->response->setStatusCode(200, "Success");
                $this->response->setJsonContent(json_encode(['errors' => [['status' => 200, 'detail' => 'Success']]]));
            }
        }

        return $this->response;
    }

    /**
     * Login user
     * @return \Phalcon\Http\ResponseInterface
     */
    public function loginAction()
    {
        $form = new LoginForm();
        $response = new Response();

        $this->response->setStatusCode(409, "Invalid username or password");

        if ($this->request->isPost()) {
            if (!$form->isValid($this->request->getPost())) {
                foreach($form->getMessages() as $message) {
                    $response->setStatusCode(409, $message->getMessage());
                }
            } else {
                $username = $this->request->getPost('username', 'striptags');
                $password = $this->security->hash($this->request->getPost('password'));

                if(UserApp::login($username, $password)){
                    $response->setStatusCode(201, "Login");
                }
            }
        }

        return $response;
    }

    /**
     * Logout user and clear the data from session
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function signoutAction()
    {
        $response = new Response();
        $user = UserApp::current();
        $user->logout();
        $response->setStatusCode(201, "Logout");
    }

    /**
     * @todo remove view / handle error return
     * Register user
     */
    public function registerAction()
    {
        $form = new RegisterForm();

        if ($this->request->isPost()) {
            if (!$form->isValid($this->request->getPost())) {
                foreach($form->getMessages() as $message) {
                    $this->flash->error($message->getMessage());
                }
            } else {
                $user = new User();
                $user->assign(array(
                    'name' => $this->request->getPost('name', 'striptags'),
                    'email' => $this->request->getPost('email'),
                    'password' => $this->security->hash($this->request->getPost('password')),
                    'group_id' => 2,
                    //'banned' => 0,
                    //'suspended' => 0
                ));

                if (!$user->save()) {
                    foreach($user->getMessages() as $message) {
                        $this->flash->error($message->getMessage());
                    }
                } else {
                    $this->view->disable();
                    return $this->response->redirect($this->_activeLanguage.'/user/register');
                }
            }
        }

        $this->view->form = $form;
        echo $this->view->render('users/register');
    }

    /**
     * Shows the forgot password form
     */
    public function forgotPasswordAction()
    {
        $form = new ForgotPasswordForm();

        if ($this->request->isPost())
        {
            if (!$form->isValid($this->request->getPost()))
            {
                foreach ($form->getMessages() as $message)
                {
                    $this->flash->error($message);
                }
            }
            else
            {
                $email = trim(strtolower($this->request->getPost('email')));
                $user  = User::findFirstByEmail($email);
                if (!$user)
                {
                    $this->flash->error('There is no account associated to this email');
                }
                else
                {
                    $resetPassword = new UserResetPasswords();
                    $resetPassword->setUserId($user->getId());
                    if ($resetPassword->save())
                    {
                        $this->flashSession->success('Success! Please check your messages for an email reset password');
                        $this->view->disable();
                        return $this->response->redirect($this->_activeLanguage.'/user/forgotPassword');
                    }
                    else
                    {
                        foreach ($resetPassword->getMessages() as $message)
                        {
                            $this->flash->error($message);
                        }
                    }
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * Reset pasword
     */
    public function resetPasswordAction($code, $email)
    {
        $resetPassword = UserResetPasswords::findFirstByCode($code);

        if (!$resetPassword) {
            $this->flash->error('Invalid or expired code');
            return $this->dispatcher->forward(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        }

        if ($resetPassword->getReset() <> 0) {
            return $this->dispatcher->forward(array(
                'controller' => 'user',
                'action' => 'login'
            ));
        }

        $resetPassword->setReset(1);

        /**
         * Change the confirmation to 'reset'
         */
        if (!$resetPassword->save()) {

            foreach ($resetPassword->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        }

        /**
         * Identity the user in the application
         */
        $this->auth->authUserById($resetPassword->getUserId());

        $this->flash->success('Please reset your password');

        return $this->dispatcher->forward(array(
            'controller' => 'user',
            'action' => 'changePassword'
        ));

    }

    /**
     * Users must use this action to change its password
     *
     */
    public function changePasswordAction()
    {
        $form = new ChangePasswordForm();

        if ($this->request->isPost()) {
            if (!$form->isValid($this->request->getPost())) {
                foreach ($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {
                $user = $this->auth->getUser();

                $user->setPassword($this->security->hash($this->request->getPost('password')));
                $user->setMustChangePassword(0);

                $passwordChange = new UserPasswordChanges();
                $passwordChange->user = $user;
                $passwordChange->setIpAddress($this->request->getClientAddress());
                $passwordChange->setUserAgent($this->request->getUserAgent());

                if (!$passwordChange->save()) {
                    $this->flash->error($passwordChange->getMessages());
                } else {

                    $this->flashSession->success('Your password was successfully changed');
                    $this->view->disable();
                    return $this->response->redirect($this->_activeLanguage.'/user/changePassword');
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * Confirms an e-mail, if the user must change its password then changes it
     */
    public function confirmEmailAction($code, $email)
    {
        $confirmation = UserEmailConfirmations::findFirstByCode($code);

        if (!$confirmation) {
            $this->flash->error('Invalid or expired code');
            return $this->dispatcher->forward(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        }

        if ($confirmation->getConfirmed() <> 0) {
            $this->flash->notice('This account is already activated. You can login.');
            return $this->dispatcher->forward(array(
                'controller' => 'user',
                'action' => 'login'
            ));
        }

        $confirmation->setConfirmed(1);
        $confirmation->user->setActive(1);

        if (!$confirmation->save()) {

            foreach ($confirmation->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        }

        $this->auth->authUserById($confirmation->user->getId());

        if ($confirmation->user->getMustChangePassword() == 1) {

            $this->flash->success('The email was successfully confirmed. Now you must change your password');
            return $this->response->redirect($this->_activeLanguage.'/user/changePassword');
        }

        $this->flash->success('The email was successfully confirmed');

        return $this->response->redirect($this->_activeLanguage.'/user/profile');
    }

    public function profileAction()
    {

    }
}