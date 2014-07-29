<?php

namespace EnterMobile\Model\Form\User;

class ResetForm {
    /** @var string */
    public $url;
    /** @var string */
    public $username;
    /** @var string[] */
    public $errors = [];
    /** @var bool */
    public $isHidden;


    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['username'])) $this->username = (string)$data['username'];
    }
}