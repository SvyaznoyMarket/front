<?php

namespace EnterModel\Product;

use EnterModel as Model;

class PartnerOffer {
    /** @var Partner */
    public $partner;
    /** @var int */
    public $deliveryDayCount = 0;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->partner = new Partner($data);
        if (Partner::TYPE_SLOT == $this->partner->type) $this->deliveryDayCount = 3;
    }
}