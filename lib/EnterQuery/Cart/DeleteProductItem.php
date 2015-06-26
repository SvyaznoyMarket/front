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
     */
    public function __construct($productUi, $userUi) {
        $this->url = new Url();
        $this->url->path = 'api/cart/remove';
        $this->data = [
            'uid' => $productUi,
            'user_uid' => $userUi,
            'quantity' => 9999999, // Если не передать большое quantity, то вместо удаления товара будет уменьшено его кол-во
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