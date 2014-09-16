<?php
namespace EnterModel\Cart\Split\Order;

class Seller {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $offer;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->offer = !empty($data['offer']) ? (string)$data['offer'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'offer' => $this->offer,
        ];
    }
}
