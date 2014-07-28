<?php

namespace EnterMobile\Model\Form\User;

class AuthForm {
    /** @var string */
    public $url;
    /** @var string */
    public $username;
    /** @var string */
    public $password;
    /** @var string[] */
    public $errors = [];
    /** @var bool */
    public $isHidden = false;
}