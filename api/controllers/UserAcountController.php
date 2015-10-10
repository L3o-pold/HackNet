<?php

namespace HackNet\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\UserPlugin\Models\User\User;
use Phalcon\UserPlugin\Models\User\UserProfile;
use Phalcon\UserPlugin\Models\User\UserPasswordChanges;

use Phalcon\UserPlugin\Forms\User\UserForm;
use Phalcon\UserPlugin\Forms\User\UserProfileForm;
use Phalcon\UserPlugin\Forms\User\ChangePasswordForm;

use Phalcon\Tag;

class UserAccountController extends Controller
{

    public function indexAction()
    {
        return $this->dispatcher->forward(array('action' => 'edit'));
    }

    /**
     * The basic account form
     */
    public function editAction()
    {
        $identity = $this->auth->getIdentity();
        $user = User::findFirstById($identity['id']);

        if (!$user) {
            $this->flash->error("User was not found");
            return $this->dispatcher->forward(array('action' => 'index'));
        }

        $form = new UserForm($user, array('edit' => true));

        if ($this->request->isPost()) {
            if(!$form->isValid($this->request->getPost())) {
                foreach($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {
                $user->assign(array(
                    'name' => $this->request->getPost('name', 'striptags'),
                    'email' => $this->request->getPost('email', 'email'),
                ));

                if (!$user->save()) {
                    $this->flash->error($user->getMessages());
                } else {
                    $form = new UserForm($user, array('edit' => true));
                    $this->flash->success("Data has been successfully saved");
                    Tag::resetInput();
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * Profile section
     */
    public function profileAction()
    {
        $identity = $this->auth->getIdentity();

        $profile = UserProfile::findFirstByUserId($identity['id']);

        if (!$profile) { // Create a profile record if does not exists
            $profile = new UserProfile();
            $profile->setUserId($identity['id']);
            $profile->setBirthDate('1900-01-01');
            if(!$profile->save()){
                foreach($profile->getMessages() as $message) {
                    $this->flash->error($message);
                }
            }
        }

        $form = new UserProfileForm($profile, array('edit' => true));

        if ($this->request->isPost()) {

            $picture = null;
            if ($this->request->hasFiles() == true) {
                foreach ($this->request->getUploadedFiles() as $file) {
                    $fileLocation = $this->config->application->upload_dir . 'profile_pictures/' . $file->getName();
                    if(true == $file->moveTo($fileLocation)){
                        $picture = $file->getName();
                    }
                }
            }

            if(!$form->isValid($this->request->getPost())) {
                foreach($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {

                $profile->assign(array(
                    'birth_date' => $this->request->getPost('birth_date'),
                    'home_location_id' => $this->request->getPost('home_location_id'),
                    'current_location_id' => $this->request->getPost('current_location_id'),
                    'picture' => $picture
                ));

                if (!$profile->save()) {
                    $this->flash->error($profile->getMessages());
                } else {
                    $form = new UserProfileForm($profile, array('edit' => true));
                    $this->flash->success("Data has been successfully saved");
                    Tag::resetInput();
                }
            }
        }

        $location = array(
            'home' => array(
                'name' => $profile->getHomeLocationId() ? $profile->homeLocation->getCustomFormattedAddress() : '',
                'id' => $profile->getHomeLocationId(),
            ),
            'current' => array(
                'name' => $profile->getCurrentLocationId() ? $profile->currentLocation->getCustomFormattedAddress() : '',
                'id' => $profile->getCurrentLocationId(),
            ),
        );

        $this->view->setVar('location', $location);
        $this->view->setVar('profile', $profile);

        $this->view->form = $form;
    }
}