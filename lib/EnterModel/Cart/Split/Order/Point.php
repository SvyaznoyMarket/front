<?php

namespace EnterModel\Cart\Split\Order;

class Point {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $groupToken;
    /** @var string|null */
    public $nearestDay;
    /** @var Point\DateInterval|null */
    public $dateInterval;
    /** @var int */
    public $cost;
    /** @var bool */
    public $fitsAllProducts = true;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->nearestDay = $data['nearest_day'] ? (string)$data['nearest_day'] : null;
        $this->cost = $data['cost'] ? (int)$data['cost'] : 0;
        if (isset($data['date_interval']['from']) || isset($data['date_interval']['to'])) {
            $this->dateInterval = new Point\DateInterval($data['date_interval']);
        }

        if (isset($data['fits_all_products'])) {
            $this->fitsAllProducts = (bool)$data['fits_all_products'];
        }
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
