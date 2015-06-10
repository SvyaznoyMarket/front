<?php

namespace EnterModel\Payment;

class PsbForm {
    /** @var string */
    public $url;
    /** @var string */
    public $amount;
    /** @var string */
    public $currency;
    /** @var string */
    public $order;
    /** @var string */
    public $desc;
    /** @var string */
    public $terminal;
    /** @var string */
    public $trtype;
    /** @var string */
    public $merchName;
    /** @var string */
    public $merchant;
    /** @var string */
    public $email;
    /** @var string */
    public $timestamp;
    /** @var string */
    public $nonce;
    /** @var string */
    public $backref;
    /** @var string */
    public $pSign;

    /**
     * @param array $data
     */
    public function __construct(array $data) {
        if (array_key_exists('AMOUNT', $data)) $this->amount = (string)$data['AMOUNT'];
        if (array_key_exists('CURRENCY', $data)) $this->currency = (string)$data['CURRENCY'];
        if (array_key_exists('ORDER', $data)) $this->order = (string)$data['ORDER'];
        if (array_key_exists('DESC', $data)) $this->desc = (string)$data['DESC'];
        if (array_key_exists('TERMINAL', $data)) $this->terminal = (string)$data['TERMINAL'];
        if (array_key_exists('TRTYPE', $data)) $this->trtype = (string)$data['TRTYPE'];
        if (array_key_exists('MERCH_NAME', $data)) $this->merchName = (string)$data['MERCH_NAME'];
        if (array_key_exists('MERCHANT', $data)) $this->merchant = (string)$data['MERCHANT'];
        if (array_key_exists('EMAIL', $data)) $this->email = (string)$data['EMAIL'];
        if (array_key_exists('TIMESTAMP', $data)) $this->timestamp = (string)$data['TIMESTAMP'];
        if (array_key_exists('NONCE', $data)) $this->nonce = (string)$data['NONCE'];
        if (array_key_exists('BACKREF', $data)) $this->backref = (string)$data['BACKREF'];
        if (array_key_exists('P_SIGN', $data)) $this->pSign = (string)$data['P_SIGN'];
    }
}