<?php
namespace EnterModel\Cart\Split\Order;

class Seller {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $offer;

    public function __construct($data = []) {
        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['offer'])) {
            $this->offer = (string)$data['offer'];
        }
    }
}
