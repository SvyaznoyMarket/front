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

    public function __construct($data = []) {
        $this->ui = $data['uid'] ? (string)$data['uid'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->discount = $data['discount'] ? (string)$data['discount'] : null;
        $this->type = $data['type'] ? (string)$data['type'] : null;
        $this->number = $data['number'] ? (string)$data['number'] : null;
    }
}
