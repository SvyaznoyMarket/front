<?php

namespace EnterQuery\Cart\Split;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItem extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\Cart $cart
     * @param string $regionId
     * @param Model\Shop|null $shop
     * @param \EnterModel\PaymentMethod|null $paymentMethod
     * @param array $split
     * @param array $change
     */
    public function __construct(
        Model\Cart $cart,
        $regionId,
        Model\Shop $shop = null,
        Model\PaymentMethod $paymentMethod = null,
        array $split = [],
        array $change = []
    ) {
        $this->url = new Url();
        $this->url->path = 'v2/cart/split';
        $this->url->query = [
            'geo_id' => $regionId,
        ];
        $this->data = [
            'cart' => [
                'product_list'       => array_values(array_map(function(Model\Cart\Product $cartProduct) {
                    return [
                        'id'       => $cartProduct->id,
                        'quantity' => $cartProduct->quantity,
                    ];
                }, $cart->product)),
                'shop_id'            => $shop ? $shop->id : null,
                'payment_method_id'  => $paymentMethod ? $paymentMethod->id : null,
            ],
        ];

        if ((bool)$split && (bool)$change) {
            $this->data['previous_split'] = $split;
            $this->data['changes'] = $change;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['delivery_groups']) ? $data : null;
    }
}