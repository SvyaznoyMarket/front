<?php

namespace EnterQuery\User\Favorite;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class CheckListByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;


    public function __construct($userUi, array $productUis) {
        $this->url = new Url();
        $this->url->path = 'api/favorite/check';
        $this->url->query = [
            'user_uid' => $userUi,
            'products' => $productUis,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['products']) ? $data['products'] : [];
    }
}