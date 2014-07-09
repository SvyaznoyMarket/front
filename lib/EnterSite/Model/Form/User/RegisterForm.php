<?php

namespace EnterSite\Model\Form\User;

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
}