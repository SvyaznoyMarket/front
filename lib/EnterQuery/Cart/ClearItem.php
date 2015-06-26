<?php

namespace EnterQuery\Cart;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class ClearItem extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $userUi
     */
    public function __construct($userUi) {
        $this->url = new Url();
        $this->url->path = 'api/cart/flush';
        $this->data = [
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