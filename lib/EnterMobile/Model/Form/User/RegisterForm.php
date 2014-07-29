<?php

namespace EnterMobile\Model\Form\User;

class RegisterForm {
    /** @var string */
    public $url;
    /** @var string */
    public $redirectUrl;
    /** @var string */
    public $name;
    /** @var string */
    public $email;
    /** @var string */
    public $phone;
    /** @var bool */
    public $subscribe;
    /** @var string[] */
    public $errors = [];
    /** @var bool */
    public $isHidden;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['name'])) $this->name = (string)$data['name'];
        if (isset($data['email'])) $this->email = (string)$data['email'];
        if (isset($data['phone'])) $this->phone = (string)$data['phone'];
    }
}