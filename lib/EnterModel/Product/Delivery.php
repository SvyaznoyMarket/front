<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Delivery {
    const ID_NOW = '4';

    const TOKEN_STANDARD = 'standart';
    const TOKEN_SELF = 'self';
    const TOKEN_NOW = 'now';
    const TOKEN_PICKPOINT = 'self_partner_pickpoint';
    const TOKEN_HERMES = 'self_partner_hermes';
    const TOKEN_EUROSET = 'self_partner_euroset';

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
    public $nearestDeliveredAt;
    /** @var bool */
    public $isPickup = false;
    /** @var \DateTime[] */
    public $dates = [];
    /** @var \EnterModel\DateInterval|null */
    public $dateInterval;
}