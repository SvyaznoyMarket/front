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
     */
    public function __construct($data = []) {
        foreach ($data['delivery_groups'] as $item) {
            $this->deliveryGroups[] = new Split\DeliveryGroup($item);
        }

        foreach ($data['delivery_methods'] as $item) {
            $this->deliveryMethods[] = new Split\DeliveryMethod($item);
        }

        foreach ($data['payment_methods'] as $item) {
            $this->paymentMethods[] = new Split\PaymentMethod($item);
        }

        foreach ($data['points'] as $token => $item) {
            $item['token'] = $token;
            $this->pointGroups[] = new Split\PointGroup($item);
        }

        foreach ($data['orders'] as $item) {
            $this->orders[] = new Split\Order($item);
        }

        $this->user = $data['user_info'] ? new Split\User($data['user_info']) : null;
        $this->sum = (string)$data['total_cost'];

        if (isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $item) {
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
            'orders'           => array_map(function(Split\Order $product) { return $product->dump(); }, $this->orders),
            'user_info'        => $this->user ? $this->user->dump() : null,
            'total_cost'       => $this->sum,
            'errors'           => array_map(function(Model\Cart\Split\Error $error) { return $error->dump(); }, $this->errors),
        ];
    }
}