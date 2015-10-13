<?php

use Phalcon\Mvc\Model as Model;

/**
 * @author  Léopold Jacquot
 */
class FileModel extends Model {

    public $id;
    public $fileName;
    public $fileContent;
    public $userId;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'file';
    }


    /**
     * @return bool
     */
    public function validation() {
        $this->validate(new \Phalcon\Mvc\Model\Validator\StringLength([
            'field' => 'fileName',
            'max' => 50,
            'min' => 1,
            'messageMaximum' => 'We don\'t like really long fileName',
            'messageMinimum' => 'We want a longer fileName'
        ]));

        /**
         * @TODO Check userId and fileContent
         */
        return $this->validationHasFailed() != true;
    }
}