<?php

namespace EnterModel\Cart\Split\Order;

class PaymentMethod {
    /** @var string|null */
    public $id;
    /** @var PaymentMethod\Discount|null */
    public $discount;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        if (isset($data['id'])) $this->id = (string)$data['id'];
        if (isset($data['discount']['value'])) $this->discount = new PaymentMethod\Discount($data['discount']);
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'          => $this->id,
            'discount'    => $this->discount,
        ];
    }
}
