<?php
namespace EnterModel\Cart\Split\Order;

class Point {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $nearestDay;
    /** @var int */
    public $cost;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->nearestDay = $data['nearest_day'] ? (string)$data['nearest_day'] : null;
        $this->cost = $data['cost'] ? (int)$data['cost'] : 0;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'          => $this->id,
            'nearest_day' => $this->nearestDay,
            'cost'        => $this->cost,
        ];
    }
}
