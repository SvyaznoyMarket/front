<?php

namespace EnterModel\PaymentMethod;

class Discount {
    /** @var string */
    public $code;
    /** @var string */
    public $value = '';
    /** @var string */
    public $unit = '';
    /**
     * @param array $data
     */
    public function __construct($data = []) {

        if (isset($data['code'])) $this->code = (string)$data['code'];
        if (isset($data['value'])) $this->value = (string)$data['value'];
        if (isset($data['unit'])) $this->unit = (string)$data['unit'];
    }
    /**
     * @return array
     */
    public function dump() {
        return [
            'value' => $this->value,
            'unit'  => $this->unit,
        ];
    }
}