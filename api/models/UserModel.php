<?php

use Phalcon\Mvc\Model as Model;

/**
 * @author  Léopold Jacquot
 */
class UserModel extends Model {

    /**
     * @var string $title
     */
    public $id;
    public $email;
    public $userAppId;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'user';
    }

    public function initialize()
    {
        $this->hasMany('id', 'FileModel', 'userId');
    }

    /**
     * @return bool
     */
    public function validation() {
        $this->validate(
            new \Phalcon\Mvc\Model\Validator\Email(
                [
                    'field'          => 'email',
                    'max'            => 50,
                    'min'            => 3,
                    'messageMaximum' => 'We don\'t like really long names',
                    'messageMinimum' => 'We want the full name'
                ]
            )
        );

        $this->validate(
            new \Phalcon\Mvc\Model\Validator\StringLength(
                [
                    'field'          => 'password',
                    'max'            => 50,
                    'min'            => 3,
                    'messageMaximum' => 'We don\'t like really long password',
                    'messageMinimum' => 'We want a longer password'
                ]
            )
        );

        return $this->validationHasFailed() != true;
    }
}