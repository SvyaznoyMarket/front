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

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->description = $data['description'] ? (string)$data['description'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'          => $this->id,
            'ui'          => $this->ui,
            'name'        => $this->name,
            'description' => $this->description,
        ];
    }
}
