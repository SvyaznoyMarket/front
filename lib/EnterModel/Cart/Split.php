<?php

namespace EnterModel\Cart;
use EnterModel as Model;

class Split {
    /** @var Model\Region|null */
    public $region;
    /** @var Split\DeliveryGroup[] */
    public $deliveryGroups = [];
    /** @var Split\DeliveryMethod[] */
    public $deliveryMethods = [];
    /** @var Split\PaymentMethod[] */
    public $paymentMethods = [];
    /** @var Split\PointGroup[] */
    public $pointGroups = [];
    /** @var Split\Order[] */
    public $orders = [];
    /** @var Split\User|null */
    public $user;
    /** @var string|null */
    public $clientIp;
    /** @var string|null */
    public $sum;
    /** @var Model\Cart\Split\Error[] */
    public $errors = [];

    public function __construct($data = []) {
        if (isset($data['delivery_groups']) && is_array($data['delivery_groups'])) {
            foreach ($data['delivery_groups'] as $item) {
                $this->deliveryGroups[] = new Split\DeliveryGroup($item);
            }
        }

        if (isset($data['delivery_methods']) && is_array($data['delivery_methods'])) {
            foreach ($data['delivery_methods'] as $item) {
                $this->deliveryMethods[] = new Split\DeliveryMethod($item);
            }
        }

        if (isset($data['payment_methods']) && is_array($data['payment_methods'])) {
            foreach ($data['payment_methods'] as $item) {
                $this->paymentMethods[] = new Split\PaymentMethod($item);
            }
        }

        if (isset($data['points']) && is_array($data['points'])) {
            foreach ($data['points'] as $token => $item) {
                $item['token'] = $token;
                $this->pointGroups[] = new Split\PointGroup($item);
            }
        }

        if (isset($data['orders']) && is_array($data['orders'])) {
            foreach ($data['orders'] as $item) {
                $this->orders[] = new Split\Order($item);
            }
        }

        if (isset($data['user_info'])) {
            $this->user = new Split\User($data['user_info']);
        }

        if (isset($data['total_cost'])) {
            $this->sum = (string)$data['total_cost'];
        }

        if (isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $item) {
                $this->errors[] = new Split\Error($item);
            }
        }
    }
}