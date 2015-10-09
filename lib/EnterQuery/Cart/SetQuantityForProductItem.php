<?php

namespace EnterQuery\Cart;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

// TODO переименовать в MergeProduct
class SetQuantityForProductItem extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $productUi
     * @param string $quantity
     * @param string $userUi
     */
    public function __construct($productUi, $quantity, $userUi) {
        $this->url = new Url();
        $this->url->path = 'api/cart/set';
        $this->data = [
            'uid'      => $productUi,
            'quantity' => $quantity,
            'user_uid' => $userUi,
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