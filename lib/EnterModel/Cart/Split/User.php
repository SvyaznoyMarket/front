<?php
namespace EnterModel\Cart\Split;

class User {
    /** @var string|null */
    public $phone;
    /** @var string|null */
    public $lastName;
    /** @var string|null */
    public $firstName;
    /** @var string|null */
    public $email;
    /** @var User\Address|null */
    public $address;
    /** @var string|null */
    public $bonusCardNumber;
    /** @var string|null */
    public $smsCode;

    public function __construct($data = []) {
        if ($data['phone']) {
            $this->phone = trim((string)$data['phone']);
            $this->phone = preg_replace('/^\+7/', '8', $this->phone);
            $this->phone = preg_replace('/[^\d]/', '', $this->phone);
            if (10 == strlen($this->phone)) {
                $this->phone = '8' . $this->phone;
            }
        }

        $this->lastName = $data['last_name'] ? (string)$data['last_name'] : null;
        $this->firstName = $data['first_name'] ? (string)$data['first_name'] : null;
        $this->email = $data['email'] ? (string)$data['email'] : null;
        $this->address = $data['address'] ? new User\Address($data['address']) : null;
        $this->bonusCardNumber = $data['bonus_card_number'] ? (string)$data['bonus_card_number'] : null;
    }
}
