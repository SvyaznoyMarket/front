<?php

namespace EnterQuery\User\Favorite;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class SetItemByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;


    public function __construct($userUi, Model\Product $product) {
        $this->url = new Url();
        $this->url->path = 'api/favorite/add';
        $this->data = [
            'user_uid' => $userUi,
            'uid'      => $product->ui,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}