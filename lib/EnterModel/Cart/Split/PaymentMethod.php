<?php
namespace EnterModel\Cart\Split;

class PaymentMethod {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $description;

    public function __construct($data = []) {
        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['ui'])) {
            $this->ui = (string)$data['ui'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['description'])) {
            $this->description = (string)$data['description'];
        }
    }
}
