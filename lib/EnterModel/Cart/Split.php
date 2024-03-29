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

    /**
     * @param array $data
     * @param bool $format
     */
    public function __construct($data = [], $format = true) {
        foreach ($data['delivery_groups'] as $key => $item) {
            if ($format) {
                $this->deliveryGroups[] = new Split\DeliveryGroup($item);
            } else {
                $this->deliveryGroups[$key] = new Split\DeliveryGroup($item);
            }
        }

        foreach ($data['delivery_methods'] as $key => $item) {
            if ($format) {
                $this->deliveryMethods[] = new Split\DeliveryMethod($item);
            } else {
                $this->deliveryMethods[$key] = new Split\DeliveryMethod($item);
            }
        }

        foreach ($data['payment_methods'] as $item) {
            $this->paymentMethods[] = new Split\PaymentMethod($item);
        }

        foreach ((array)$data['points'] as $key => $item) {
            $item['token'] = $key;
            if ($format) {
                $this->pointGroups[] = new Split\PointGroup($item);
            } else {
                $this->pointGroups[$key] = new Split\PointGroup($item, $format);
            }
        }

        foreach ($data['orders'] as $key => $item) {
            $order = new Split\Order($item, $format);

            if ($format) {
                $this->orders[] = $order;
            } else {
                $this->orders[$key] = $order;
            }
        }

        $this->user = $data['user_info'] ? new Split\User($data['user_info'], $format) : null;
        $this->sum = $data['total_cost'] ? (string)$data['total_cost'] : null;

        if (isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $item) {
                if ($item['code'] == 0 && $item['message'] === 'Невозможно применить купон: ' && !isset($item['details']['coupon_number'])) {
                    continue;
                }

                $this->errors[] = new Split\Error($item);
            }
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'delivery_groups'  => array_map(function(Split\DeliveryGroup $deliveryGroup) { return $deliveryGroup->dump(); }, $this->deliveryGroups),
            'delivery_methods' => array_map(function(Split\DeliveryMethod $deliveryMethod) { return $deliveryMethod->dump(); }, $this->deliveryMethods),
            'payment_methods'  => array_map(function(Split\PaymentMethod $paymentMethod) { return $paymentMethod->dump(); }, $this->paymentMethods),
            'points'           => array_map(function(Split\PointGroup $pointGroup) { return $pointGroup->dump(); }, $this->pointGroups),
            'orders'           => array_map(function(Split\Order $order) { return $order->dump(); }, $this->orders),
            'user_info'        => $this->user ? $this->user->dump() : null,
            'total_cost'       => $this->sum,
            'errors'           => array_map(function(Model\Cart\Split\Error $error) { return $error->dump(); }, $this->errors),
        ];
    }
}