<?php
namespace EnterModel\Cart\Split;

class DeliveryGroup {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $name;

    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
    }
}
