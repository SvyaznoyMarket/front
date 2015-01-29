<?php

namespace EnterQuery\User\Favorite;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class DeleteListByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;


    public function __construct($userUi) {
        $this->url = new Url();
        $this->url->path = 'api/favorite/flush';
        $this->data = [
            'user_uid' => $userUi,
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