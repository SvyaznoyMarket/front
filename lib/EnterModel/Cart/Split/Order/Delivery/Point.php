<?php
namespace EnterModel\Cart\Split\Order\Delivery;

class Point {
    /** @var string|null */
    public $groupToken;
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;

    public function __construct($data = []) {
        if (isset($data['token'])) {
            $this->groupToken = (string)$data['token'];
        }

        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['ui'])) {
            $this->ui = (string)$data['ui'];
        }
    }
}
