<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Delivery {
    /** @var float */
    public $price;
    /** @var int */
    public $date;
    /** @var string */
    public $typeId;
    /** @var Model\DeliveryType|null */
    public $type;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        // FIXME Жуткий костыль для ядра
        if (empty($data['delivery_id'])) {
            if (array_key_exists('delivery_type_id', $data)) $this->typeId = (string)$data['delivery_type_id'];
        } else {
            if (array_key_exists('delivery_id', $data)) $this->typeId = (string)$data['delivery_id'];
        }

        if (array_key_exists('price', $data)) $this->price = (float)$data['price'];
        if (array_key_exists('delivery_date', $data) && $data['delivery_date'] && ('0000-00-00' != $data['delivery_date'])) {
            try {
                $this->date = (new \DateTime($data['delivery_date']))->getTimestamp();
            } catch(\Exception $e) {}
        }
    }
}