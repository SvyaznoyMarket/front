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
}