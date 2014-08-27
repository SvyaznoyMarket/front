<?php
namespace EnterModel\Cart\Split;

class DeliveryGroup {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $name;

    public function __construct($data = []) {
        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }
    }
}
