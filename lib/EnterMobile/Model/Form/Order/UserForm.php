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
        if (isset($data['first_name'])) $this->firstName = (string)$data['first_name'];
        if (isset($data['mnogoru_number'])) $this->mnogoruNumber = (string)$data['mnogoru_number'];
    }
}