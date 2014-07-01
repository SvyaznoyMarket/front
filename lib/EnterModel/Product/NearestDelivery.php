<?php

namespace EnterModel\Product;

use EnterModel as Model;

class NearestDelivery {
    const ID_NOW = '4';

    const TOKEN_STANDARD = 'standart';
    const TOKEN_SELF = 'self';
    const TOKEN_NOW = 'now';

    /** @var string */
    public $id;
    /** @var string */
    public $token;
    /** @var string */
    public $productId;
    /** @var string */
    public $price;
    /** @var Model\Shop[] */
    public $shopsById = [];
    /** @var \DateTime|null */
    public $deliveredAt;
}