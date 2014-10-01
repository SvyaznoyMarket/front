<?php
namespace EnterModel\Cart\Split\Order;

class Discount {
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $discount;
    /** @var string|null */
    public $type;
    /** @var string|null */
    public $number;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->ui = $data['uid'] ? (string)$data['uid'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->discount = $data['discount'] ? (string)$data['discount'] : null;
        $this->type = $data['type'] ? (string)$data['type'] : null;
        $this->number = isset($data['number']) ? (string)$data['number'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'ui'       => $this->ui,
            'name'     => $this->name,
            'discount' => $this->discount,
            'type'     => $this->type,
            'number'   => $this->number,
        ];
    }
}
