<?php
namespace EnterModel\Cart\Split\Order;

class Action {
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
        if (isset($data['uid'])) {
            $this->ui = (string)$data['uid'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['discount'])) {
            $this->discount = (string)$data['discount'];
        }

        if (isset($data['type'])) {
            $this->type = (string)$data['type'];
        }

        if (isset($data['number'])) {
            $this->number = (string)$data['number'];
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'uid'      => $this->ui,
            'name'     => $this->name,
            'discount' => $this->discount,
            'type'     => $this->type,
            'number'   => $this->number,
        ];
    }
}
