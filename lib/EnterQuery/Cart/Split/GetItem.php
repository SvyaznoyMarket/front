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
     * @param Model\Region $region
     * @param Model\Shop|null $shop
     * @param Model\PaymentMethod|null $paymentMethod
     * @param array $previousSplit
     * @param array $change
     */
    public function __construct(
        Model\Cart $cart,
        Model\Region $region,
        Model\Shop $shop = null,
        Model\PaymentMethod $paymentMethod = null,
        array $previousSplit = [],
        array $change = []
    ) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/cart/split';
        $this->url->query = [];

        if ($region->id != null) {
            $this->url->query['geo_id'] = $region->id;
        } else if ($region->ui != null) {
            $this->url->query['geo_ui'] = $region->ui;
        } else if ($region->code != null) {
            $this->url->query['geo_code'] = $region->code;
        }

        if ($shop) {
            if ($shop->id != null) {
                $this->data['shop_id'] = $shop->id;
            } else if ($shop->ui != null) {
                $this->data['shop_ui'] = $shop->ui;
            }
        }

        if ($paymentMethod) {
            if ($paymentMethod->id != null) {
                $this->data['payment_method_id'] = $paymentMethod->id;
            } else if ($paymentMethod->ui != null) {
                $this->data['payment_method_ui'] = $paymentMethod->ui;
            }
        }

        if ($previousSplit && $change) {
            $this->data['previous_split'] = $previousSplit;
            $this->data['changes'] = $change;
        } else {
            $this->data['cart'] = [
                'product_list' => array_values(array_map(function(Model\Cart\Product $cartProduct) {
                    return array_merge(
                        ['quantity' => $cartProduct->quantity],
                        $cartProduct->id ? ['id' => $cartProduct->id] : ['ui' => $cartProduct->ui],
                        (bool)$cartProduct->sender ? ['meta_data' => ['sender' => $cartProduct->sender]] : []
                    );
                }, $cart->product)),
            ];
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