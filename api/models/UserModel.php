<?php

use Phalcon\Mvc\Collection as Collection;

/**
 * @author  Léopold Jacquot
 */
class UserModel extends Collection {

    /**
     * @var string $title
     */
    public $email;

    /**
     * @return bool
     */
    public function validation() {
        $this->validate(new \Phalcon\Mvc\Model\Validator\Email([
                    'field' => 'email',
                    'max' => 50,
                    'min' => 3,
                    'messageMaximum' => 'We don\'t like really long names',
                    'messageMinimum' => 'We want the full name'
                ]));

        $this->validate(new \Phalcon\Mvc\Model\Validator\StringLength([
                    'field' => 'password',
                    'max' => 50,
                    'min' => 3,
                    'messageMaximum' => 'We don\'t like really long password',
                    'messageMinimum' => 'We want a longer password'
                ]));

        return $this->validationHasFailed() != true;
    }
}