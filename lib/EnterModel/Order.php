<?php

namespace EnterModel;

use EnterModel as Model;

class Order {
    const TYPE_ORDER = 1;
    const TYPE_PREORDER = 2;
    const TYPE_CUSTOM = 3;
    const TYPE_1CLICK = 9;

    const STATUS_FORMED = 1;
    const STATUS_READY = 6;
    const STATUS_APPROVED_BY_CALL_CENTER = 2;
    const STATUS_FORMED_IN_STOCK = 3;
    const STATUS_IN_DELIVERY = 4;
    const STATUS_DELIVERED = 5;
    const STATUS_CANCELED = 100;

    const PAYMENT_STATUS_NOT_PAID = 1;  // не оплачен
    const PAYMENT_STATUS_TRANSFER = 4;  // начало оплаты
    const PAYMENT_STATUS_ADVANCE = 3;   // частично оплачен
    const PAYMENT_STATUS_PAID = 2;      // оплачен
    const PAYMENT_STATUS_CANCELED = 5;  // отмена оплаты

    /** @var string */
    public $id;
    /** @var int */
    public $typeId;
    /** @var int */
    public $statusId;
    /** @var string */
    public $number;
    /** @var string */
    public $numberErp;
    /** @var string */
    public $lastName;
    /** @var string */
    public $firstName;
    /** @var string */
    public $middleName;
    /** @var string */
    public $mobilePhone;
    /** @var int */
    public $sum;
    /** @var string */
    public $shopId;
    /** @var int */
    public $cityId;
    /** @var int */
    public $regionId;
    /** @var string */
    public $address;
    /** @var string */
    public $comment;
    /** @var string */
    public $ipAddress;
    /** @var int */
    public $createdAt;
    /** @var int */
    public $updatedAt;
    /** @var Model\Order\Product[] */
    public $product = [];
    /** @var int */
    public $paySum;
    /** @var int */
    public $discountSum;
    /** @var string */
    public $subwayId;
    /** @var string */
    public $paymentUrl;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('type_id', $data)) $this->typeId = (int)$data['type_id'];
        if (array_key_exists('status_id', $data)) $this->statusId = (int)$data['status_id'];
        if (array_key_exists('number', $data)) $this->number = (string)$data['number'];
        if (array_key_exists('number_erp', $data)) $this->numberErp = (string)$data['number_erp'];
        if (array_key_exists('last_name', $data)) $this->lastName = (string)$data['last_name'];
        if (array_key_exists('first_name', $data)) $this->firstName = (string)$data['first_name'];
        if (array_key_exists('middle_name', $data)) $this->middleName = (string)$data['middle_name'];
        if (array_key_exists('mobile', $data)) $this->mobilePhone = (string)$data['mobile'];
        if (array_key_exists('sum', $data)) $this->sum = $data['sum'];
        if (array_key_exists('shop_id', $data)) $this->shopId = (string)$data['shop_id'];
        if (array_key_exists('geo_id', $data)) $this->cityId = (string)$data['geo_id'];
        if (array_key_exists('region_id', $data)) $this->regionId = (string)$data['region_id'];
        if (array_key_exists('address', $data)) $this->address = (string)$data['address'];
        if (array_key_exists('extra', $data)) $this->comment = (string)$data['extra'];
        if (array_key_exists('ip', $data)) $this->ipAddress = (string)$data['ip'];
        if (array_key_exists('added', $data) && $data['added'] && ('0000-00-00' != $data['added'])) {
            try {
                $this->createdAt = (new \DateTime($data['added']))->getTimestamp();
            } catch(\Exception $e) {}
        }
        if (array_key_exists('updated', $data) && $data['updated'] && ('0000-00-00' != $data['updated'])) {
            try {
                $this->updatedAt = (new \DateTime($data['updated']))->getTimestamp();
            } catch(\Exception $e) {}
        }
        if (array_key_exists('product', $data)) {
            foreach ((array)$data['product'] as $productData) {
                $this->product[] = new Model\Order\Product($productData);
            }
        }
        if (array_key_exists('pay_sum', $data)) $this->paySum = $data['pay_sum'];
        if (array_key_exists('discount_sum', $data)) $this->discountSum = $data['discount_sum'];
        if (array_key_exists('subway_id', $data)) $this->subwayId = (string)$data['subway_id'];
        if (array_key_exists('payment_url', $data)) $this->paymentUrl = (string)$data['payment_url'];
    }
}