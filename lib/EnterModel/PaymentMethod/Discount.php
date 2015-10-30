<?php

namespace EnterModel\PaymentMethod;

class Discount {
    /** @var float */
    public $value;
    /** @var string|null */
    public $unit;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->value = $data['value'] ? (float)$data['value'] : null;
        $this->unit = $data['unit'] ? (string)$data['unit'] : null;
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
