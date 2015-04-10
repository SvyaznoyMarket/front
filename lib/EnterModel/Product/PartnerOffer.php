<?php

namespace EnterModel\Product;

use EnterModel as Model;

class PartnerOffer {
    /** @var Partner */
    public $partner;
    /** @var string */
    public $productId;
    /** @var int */
    public $deliveryDayCount = 0;
    /** @var Model\Product\Stock[] */
    public $stock = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->partner = new Partner($data);
        if (Partner::TYPE_SLOT == $this->partner->type) $this->deliveryDayCount = 3;

        if (isset($data['offer_id'])) $this->productId = (string)$data['offer_id'];

        if (isset($data['stock'][0])) {
            foreach ($data['stock'] as $stockItem) {
                $this->stock[] = new Model\Product\Stock($stockItem);
            }
        }
    }
}