<?php

namespace EnterMobile\Model\Form\User;

class EditProfileForm {
    /** @var string */
    public $url;
    /** @var string */
    public $redirectUrl;
    /** @var string */
    public $firstName;
    /** @var string */
    public $lastName;
    /** @var string */
    public $middleName;
    /** @var string */
    public $birthday;
    /** @var string */
    public $sex;
    /** @var string */
    public $homePhone;
    /** @var string */
    public $occupation;
    /** @var string */
    public $email;
    /** @var string */
    public $phone;
    /** @var bool */
    public $subscribe;
    /** @var string[] */
    public $errors = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['firstName'])) $this->firstName = (string)$data['firstName'];
        if (isset($data['lastName'])) $this->lastName = (string)$data['lastName'];
        if (isset($data['middleName'])) $this->middleName = (string)$data['middleName'];
        if (isset($data['birthday'])) $this->birthday = (string)$data['birthday'];
        if (isset($data['sex'])) $this->sex = (string)$data['sex'];
        if (isset($data['homePhone'])) $this->homePhone = (string)$data['homePhone'];
        if (isset($data['occupation'])) $this->occupation = (string)$data['occupation'];
        if (isset($data['email'])) $this->email = (string)$data['email'];
        if (isset($data['phone'])) $this->phone = (string)$data['phone'];
    }
}