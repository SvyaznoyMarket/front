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

    public function __construct($data = []) {
        if (isset($data['phone'])) {
            $this->phone = trim((string)$data['phone']);
            $this->phone = preg_replace('/^\+7/', '8', $this->phone);
            $this->phone = preg_replace('/[^\d]/', '', $this->phone);
            if (10 == strlen($this->phone)) {
                $this->phone = '8' . $this->phone;
            }
        }

        if (isset($data['last_name'])) {
            $this->lastName = (string)$data['last_name'];
        }

        if (isset($data['first_name'])) {
            $this->firstName = (string)$data['first_name'];
        }

        if (isset($data['email'])) {
            $this->email = (string)$data['email'];
        }

        if (isset($data['address'])) {
            $this->address = new User\Address($data['address']);
        }

        if (isset($data['bonus_card_number'])) {
            $this->bonusCardNumber = (string)$data['bonus_card_number'];
        }
    }
}
