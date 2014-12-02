<?php

namespace EnterModel;

use EnterModel as Model;

class Coupon {
    /** @var string */
    public $number;
    /** @var string */
    public $seriesId;
    /** @var string */
    public $promoToken;
    /** @var string */
    public $name;
    /** @var string */
    public $discount;
    /** @var bool */
    public $isUsed;
    /** @var string */
    public $startAt;
    /** @var string */
    public $endAt;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('number', $data)) $this->number = (string)$data['number'];
        if (array_key_exists('series', $data)) $this->seriesId = (string)$data['series'];
        if (array_key_exists('promo', $data)) $this->promoToken = (string)$data['promo'];
        if (array_key_exists('title', $data)) $this->name = (string)$data['title'];
        if (array_key_exists('amount', $data)) $this->discount = (string)$data['amount'];
        if (array_key_exists('used', $data)) $this->isUsed = (bool)$data['used'];
        try {
            if (array_key_exists('from', $data)) $this->startAt = date('c', strtotime((string)$data['from']));
        } catch (\Exception $e) {}
        try {
            if (array_key_exists('to', $data)) $this->endAt = date('c', strtotime((string)$data['to']));
        } catch (\Exception $e) {}
    }
}
