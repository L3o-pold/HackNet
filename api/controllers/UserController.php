<?php

use Phalcon\Http\Response;
use Phalcon\Mvc\View;
use Phalcon\Tag;
use UserApp\API;

/**
 * @package UserController
 * @author  Léopold Jacquot
 */
class UserController extends MainController {

    public function indexAction() {
        $config = $this->getDI()->get('config');

        $api = new API($config->oauth->appId, $config->oauth->appToken);

        $users = $api->user->search();

        $data = [];
        foreach ($users->items as $user) {
            $data[] = [
                'id' => $user->user_id,
                'email' => $user->login
            ];
        }

        echo json_encode((object) $data);
    }

    /**
     * @param int $userId
     */
    public function getAction($userId) {
    }

    /**
     * @param int $userId
     */
    public function putAction($userId) {
    }

    /**
     *
     */
    public function postAction() {
    }

    /**
     * @param int $userId
     */
    public function deleteAction($userId) {
    }
}