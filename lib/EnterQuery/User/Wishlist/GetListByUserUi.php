<?php

namespace EnterQuery\User\Wishlist;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $userUi
     * @param bool $withProducts
     */
    public function __construct($userUi, $withProducts = false) {
        $this->url = new Url();
        $this->url->path = 'api/wishlist';
        $this->url->query = [
            'user_uid' => $userUi,
        ];
        if ($withProducts) {
            $this->url->query['with_products'] = true;
        }

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