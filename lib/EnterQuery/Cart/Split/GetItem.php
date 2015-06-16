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
     * @param Model\Cart\Split\User|null $user
     * @param bool $checkStocks
     */
    public function __construct(
        Model\Cart $cart,
        Model\Region $region,
        Model\Shop $shop = null,
        Model\PaymentMethod $paymentMethod = null,
        array $previousSplit = [],
        array $change = [],
        Model\Cart\Split\User $user = null,
        $checkStocks = true
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
                    $item = [
                        'quantity'  => $cartProduct->quantity,
                        'meta_data' => [],
                    ];
                    if ($cartProduct->id) {
                        $item['id'] = $cartProduct->id;
                    } else {
                        $item['ui'] = $cartProduct->ui;
                    }
                    if ((bool)$cartProduct->sender) {
                        $item['meta_data']['sender'] = $cartProduct->sender;
                    }

                    return $item;
                }, $cart->product)),
            ];
            if ($user) {
                $this->data['user_info'] = $user->dump();
            }
        }

        // CAPI-4
        if (!$checkStocks) {
            $this->data['check_stocks'] = $checkStocks;
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