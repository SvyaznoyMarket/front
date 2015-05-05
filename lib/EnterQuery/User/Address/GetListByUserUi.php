<?php

namespace EnterQuery\User\Address;

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
     * @param array $sort Например, [priority => DESC]
     */
    public function __construct($userUi, array $sort = null) {
        $this->url = new Url();
        $this->url->path = 'api/address';
        $this->url->query = [
            'user_uid' => $userUi,
        ];
        if ($sort) {
            $this->url->query[key($sort)] = reset($sort);
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