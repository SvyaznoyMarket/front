<?php

namespace EnterModel;

class PaymentMethod {
    const CASH_ID = '1';
    const CARD_ID = '2';
    const CREDIT_ID = '6';
    const CERTIFICATE_ID = '10';
    const WEBMONEY_ID = '11';
    const QIWI_ID = '12';
    const PAYPAL_ID = '13';

    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $description;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
    }
}