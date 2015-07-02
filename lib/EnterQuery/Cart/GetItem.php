<?php

namespace EnterQuery\Cart;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItem extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $userUi
     */
    public function __construct($userUi) {
        $this->url = new Url();
        $this->url->path = 'api/cart';
        $this->url->query = [
            'user_uid' => $userUi,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['products']) ? $data : null;
    }
}