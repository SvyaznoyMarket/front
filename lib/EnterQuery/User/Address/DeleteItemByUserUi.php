<?php

namespace EnterQuery\User\Address;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class DeleteItemByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;

    public function __construct($userUi, $id) {
        $this->url = new Url();
        $this->url->path = 'api/address/delete';
        $this->data = [
            'user_uid' => $userUi,
            'id'      => $id,
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