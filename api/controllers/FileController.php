<?php

use Phalcon\Http\Response;
use Phalcon\Mvc\View;
use Phalcon\Tag;
use UserApp\API;
use Phalcon\Http\Request\Exception;

/**
 * @package FileController
 * @author  Léopold Jacquot
 */
class FileController extends MainController {

    /**
     * @TODO Fetch files of user
     *
     * @param $userId
     */
    public function indexAction($userId) {

        $files = FileModel::find(
            array(
                "conditions" => "userId = ?1",
                "bind"       => array(1 => $userId),
                "order" => "fileName"
            )
        );

        $data = array();

        foreach ($files as $file) {
            $data[] = array(
                'id'   => $file->id,
                'fileName' => $file->fileName,
                'userId' => $file->userId
            );
        }

        echo json_encode((object) $data);
    }

    /**
     * @param int $userId
     * @param     $fileName
     */
    public function getAction($fileName) {

        vdd($this->dispatcher->getParams());

        $file = FileModel::findFirst(
            array(
                "conditions" => "userId = ?1 AND fileName = ?1",
                "bind"       => array(1 => $userId, 2 => $fileName)
            )
        );

        if ($file == false) {
            throw new Exception('Not Found', 404);
        }

        $this->response->setJsonContent(
            array(
                'status' => 'FOUND',
                'data'   => array(
                    'id'   => $file->id,
                    'fileName' => $file->fileName,
                    'userId' => $file->userId,
                    'fileContent' => $file->fileContent
                )
            )
        );

        $this->response->send();
    }

    /**
     * @param int $userId
     * @param     $fileName
     */
    public function putAction($userId, $fileName) {
    }

    /**
     * @param $userId
     */
    public function postAction($userId) {
    }

    /**
     * @param int $userId
     * @param     $fileName
     */
    public function deleteAction($userId, $fileName) {
    }
}