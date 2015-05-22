<?php

namespace EnterModel;

use EnterAggregator\PriceHelperTrait;
use EnterModel as Model;

class Order {
    use PriceHelperTrait;

    const TYPE_ORDER = 1;
    const TYPE_PREORDER = 2;
    const TYPE_CUSTOM = 3;
    const TYPE_SLOT = 4;
    const TYPE_1CLICK = 9;

    const STATUS_FORMED = 1;
    const STATUS_READY = 6;
    const STATUS_APPROVED_BY_CALL_CENTER = 2;
    const STATUS_FORMED_IN_STOCK = 3;
    const STATUS_IN_DELIVERY = 4;
    const STATUS_DELIVERED = 5;
    const STATUS_CANCELED = 100;

    const PAYMENT_STATUS_NOT_PAID = 1;  // не оплачен
    const PAYMENT_STATUS_PAID = 2;      // оплачен
    const PAYMENT_STATUS_ADVANCE = 3;   // частично оплачен
    const PAYMENT_STATUS_TRANSFER = 4;  // начало оплаты
    const PAYMENT_STATUS_CANCELED = 5;  // отмена оплаты

    /** @var string */
    public $id;
    /** @var int */
    public $typeId;
    /** @var int */
    public $statusId;
    /** @var Model\Order\Status|null */
    public $status;
    /** @var string */
    public $number;
    /** @var string */
    public $numberErp;
    /** @var string|null */
    public $token;
    /** @var float */
    public $sum;
    /** @var string */
    public $shopId;
    /** @var string */
    public $regionId;
    /** @var Model\Region|null */
    public $region;
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
    /** @var float */
    public $paySum;
    /** @var float */
    public $discountSum;
    /** @var string */
    public $subwayId;
    /** @var string */
    public $paymentMethodId;
    /** @var string|null */
    public $paymentStatusId;
    /** @var Model\Order\PaymentStatus|null */
    public $paymentStatus;
    /** @var string */
    public $paymentUrl;
    /** @var Model\Order\Delivery[] */
    public $deliveries = [];
    /** @var Model\PaymentMethod[] */
    public $paymentMethods = [];
    /** @var Model\Shop|null */
    public $shop;
    /** @var Model\Seller|null */
    public $seller;
    /** @var Model\Order\Meta[] */
    public $meta = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('type_id', $data)) $this->typeId = (int)$data['type_id'];
        if (array_key_exists('status_id', $data)) $this->statusId = (int)$data['status_id'];
        if ($this->statusId) $this->status = new Model\Order\Status(['id' => $this->statusId]);
        if (array_key_exists('number', $data)) $this->number = (string)$data['number'];
        if (array_key_exists('number_erp', $data)) $this->numberErp = (string)$data['number_erp'];
        if (array_key_exists('access_token', $data)) $this->token = $data['access_token'] ? (string)$data['access_token'] : null;
        if (array_key_exists('sum', $data)) $this->sum = $this->getPriceHelper()->removeZeroFraction($data['sum']);
        if (array_key_exists('shop_id', $data)) $this->shopId = (string)$data['shop_id'];
        if (array_key_exists('geo_id', $data)) $this->regionId = (string)$data['geo_id'];
        if (isset($data['geo']['id'])) $this->region = new Model\Region($data['geo']);
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
        if (array_key_exists('pay_sum', $data)) $this->paySum = $this->getPriceHelper()->removeZeroFraction($data['pay_sum']);
        if (array_key_exists('discount_sum', $data)) $this->discountSum = $this->getPriceHelper()->removeZeroFraction($data['discount_sum']);
        if (array_key_exists('subway_id', $data)) $this->subwayId = (string)$data['subway_id'];
        if (array_key_exists('payment_id', $data)) $this->paymentMethodId = (string)$data['payment_id'];
        if (array_key_exists('payment_status_id', $data)) $this->paymentStatusId = $data['payment_status_id']? (string)$data['payment_status_id'] : null;
        if ($this->paymentStatusId) $this->paymentStatus = new Model\Order\PaymentStatus(['id' => $this->paymentStatusId]);
        if (array_key_exists('payment_url', $data)) $this->paymentUrl = (string)$data['payment_url'];
        if (isset($data['delivery'][0])) {
            foreach ($data['delivery'] as $deliveryItem) {
                if (empty($deliveryItem['delivery_type_id'])) continue;

                $this->deliveries[] = new Model\Order\Delivery($deliveryItem);
            }
        };

        if (isset($data['seller']['ui'])) {
            $this->seller = new Model\Seller($data['seller']);
        }

        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $k => $v) {
                $meta = new Model\Order\Meta();
                $meta->key = (string)$k;
                $meta->value = $v;

                if (!$this->token && ('access_token' == $meta->key)) {
                    $this->token = is_array($meta->value) ? reset($meta->value) : $meta->value; // FIXME: костыль для ядра
                }

                $this->meta[] = $meta;
            }
        }
    }
}