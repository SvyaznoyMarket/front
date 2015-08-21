<?php

namespace EnterQuery\PaymentMethod;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetBonusCardListByCart extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $regionId
     * @param Model\Cart $cart
     */
    public function __construct($regionId, Model\Cart $cart) {
        $this->url = new Url();
        $this->url->path = 'v2/payment-method/get-bonus-card';
        $this->url->query = [
            'geo_id'     => $regionId,
        ];
        $this->data['product_list'] = array_values(array_map(function(Model\Cart\Product $cartProduct) {
            $item = [
                'quantity'  => $cartProduct->quantity,
            ];
            if ($cartProduct->id) {
                $item['id'] = $cartProduct->id;
            } else {
                $item['ui'] = $cartProduct->ui;
            }

            return $item;
        }, $cart->product));

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = is_array($data) ? $data : [];
    }
}