<?php
namespace EnterModel\Cart\Split;

class DeliveryGroup {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $name;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
