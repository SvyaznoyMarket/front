<?php

namespace EnterModel\Cart\Split\Order\PaymentMethod;

class Discount {
    /** @var float|null */
    public $value;
    /** @var string */
    public $unit = '';

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->value = $data['value'] ? (float)$data['value'] : null;
        $this->unit = $data['unit'] ? (string)$data['unit'] : '';
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
