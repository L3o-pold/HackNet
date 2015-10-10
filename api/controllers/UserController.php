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

use Phalcon\UserPlugin\Auth\Exception as AuthException;
use Phalcon\UserPlugin\Connectors\FacebookConnector;

use Phalcon\Mvc\View;
use Phalcon\Tag;

class UserController extends Controller
{

    public function indexAction()
    {
        $this->persistent->conditions = null;
        $this->view->form = new LoginForm();
        $this->view->render('users/index');
    }

    /**
     * Login user
     * @return \Phalcon\Http\ResponseInterface
     */
    public function loginAction()
    {
        if(true === $this->auth->isUserSignedIn())
        {
            $this->response->redirect(array('action' => 'profile'));
        }

        $form = new LoginForm();

        try {
            $this->auth->login($form);
        } catch (AuthException $e) {
            $this->flash->error($e->getMessage());
        }

        $this->view->form = $form;
    }

    /**
     * Login with Facebook account
     */
    public function loginWithFacebookAction()
    {
        try {
            $this->view->disable();
            return $this->auth->loginWithFacebook();
        } catch(AuthException $e) {
            $this->flash->error('There was an error connectiong to Facebook.');
        }
    }

    /**
     * Login with LinkedIn account
     */
    public function loginWithLinkedInAction()
    {
        try {
            $this->view->disable();
            $this->auth->loginWithLinkedIn();
        } catch(AuthException $e) {
            $this->flash->error('There was an error connectiong to LinkedIn.');
        }
    }

    /**
     * Login with Twitter account
     */
    public function loginWithTwitterAction()
    {
        try {
            $this->view->disable();
            $this->auth->loginWithTwitter();
        } catch(AuthException $e) {
            $this->flash->error('There was an error connectiong to Twitter.');
        }
    }

    /**
     * Login with Google account
     */
    public function loginWithGoogleAction()
    {
        try {
            $this->view->disable();
            $this->auth->loginWithGoogle();
        } catch(AuthException $e) {
            $this->flash->error('There was an error connectiong to Google.');
        }
    }

    /**
     * Logout user and clear the data from session
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function signoutAction()
    {
        $this->auth->remove();
        return $this->response->redirect('/', true);
    }

    /**
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