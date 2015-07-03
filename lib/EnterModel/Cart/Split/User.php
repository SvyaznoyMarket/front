<?php
namespace EnterModel\Cart\Split;

class User {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
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

    /**
     * @param array $data
     * @param bool $format
     */
    public function __construct($data = [], $format = true) {
        if (array_key_exists('phone', $data)) {
            $this->phone = trim((string)$data['phone']);

            if ($format) {
                $this->phone = preg_replace('/^\+7/', '8', $this->phone);
                $this->phone = preg_replace('/[^\d]/', '', $this->phone);
                if (10 == strlen($this->phone)) {
                    $this->phone = '8' . $this->phone;
                }
            }
        }

        $this->lastName = array_key_exists('last_name', $data) ? (string)$data['last_name'] : null;
        $this->firstName = array_key_exists('first_name', $data) ? (string)$data['first_name'] : null;
        $this->email = array_key_exists('email', $data) ? (string)$data['email'] : null;
        $this->address = (isset($data['address']) && is_array($data['address'])) ? new User\Address($data['address']) : null;
        //$this->bonusCardNumber = $data['bonus_card_number'] ? (string)$data['bonus_card_number'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'phone'             => $this->phone,
            'last_name'         => $this->lastName,
            'first_name'        => $this->firstName,
            'email'             => $this->email,
            'address'           => $this->address ? $this->address->dump() : null,
            'bonus_card_number' => $this->bonusCardNumber,
        ];
    }

    /**
     * @return array
     */
    public function toArray() {
        return json_decode(json_encode($this), true);
    }
}
