<?php

namespace EnterQuery\Cart;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class DeleteProductItem extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $productUi
     * @param string $userUi
     * @param int $quantity
     */
    public function __construct($productUi, $userUi, $quantity = 999999) {
        $this->url = new Url();
        $this->url->path = 'api/cart/remove';
        $this->data = [
            'uid'      => $productUi,
            'user_uid' => $userUi,
            'quantity' => $quantity,
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