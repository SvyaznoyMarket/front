<?php

namespace EnterQuery\Cart;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

// TODO переименовать в MergeProductList
class SetProductList extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param \EnterModel\Cart\Product[] $cartProducts
     * @param string $userUi
     */
    public function __construct($cartProducts, $userUi) {
        $this->url = new Url();
        $this->url->path = 'api/cart/add-batch';
        $this->data = [
            'user_uid' => $userUi,
            'products' => array_map(function(\EnterModel\Cart\Product $cartProduct) { return [
                'uid'      => $cartProduct->ui,
                'quantity' => $cartProduct->quantity ?: 1,
            ]; }, $cartProducts),
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}