<?php

namespace EnterQuery\User\Address;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $userUi
     * @param string $id
     */
    public function __construct($userUi, $id) {
        $this->url = new Url();
        $this->url->path = 'api/address';
        $this->url->query = [
            'user_uid' => $userUi,
            'id'       => $id,
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