<?php

namespace EnterMobile\Model\Form\User;

class ResetPasswordForm {
    /** @var string */
    public $url;
    /** @var string */
    public $oldPassword;
    /** @var string */
    public $newPassword;
    /** @var string */
    public $confirmPassword;
    /** @var string[] */
    public $errors = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['oldPassword'])) $this->oldPassword = (string)$data['oldPassword'];
        if (isset($data['newPassword'])) $this->newPassword = (string)$data['newPassword'];
        if (isset($data['confirmPassword'])) $this->confirmPassword = (string)$data['confirmPassword'];
    }
}