<?php

namespace EnterMobile\Model\Form\Order;

class UserForm {
    /** @var string */
    public $url;
    /** @var string */
    public $phone;
    /** @var string */
    public $email;
    /** @var string */
    public $firstName;
    /** @var string */
    public $mnogoruNumber;
    /**
     * Json-строка с ошибками формы
     * @var string
     */
    public $errorDataValue;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['phone'])) $this->phone = (string)$data['phone'];
        if (isset($data['email'])) $this->email = (string)$data['email'];
        if (isset($data['firstName'])) $this->firstName = (string)$data['firstName'];
        if (isset($data['mnogoruNumber'])) $this->mnogoruNumber = (string)$data['mnogoruNumber'];
    }

    /**
     * @return bool
     */
    public function isValid() {
        return
            !empty($this->phone)
            && !empty($this->email)
        ;
    }
}