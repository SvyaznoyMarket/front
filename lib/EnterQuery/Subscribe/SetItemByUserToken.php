<?php

namespace EnterQuery\Subscribe;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class SetItemByUserToken extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result = [];

    /**
     * @param $userToken
     * @param Model\Subscribe $subscribe
     */
    public function __construct($userToken, Model\Subscribe $subscribe) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/subscribe/set';
        $this->url->query = [
            'token' => $userToken,
        ];

        $this->data = [
            [
                'channel_id'   => $subscribe->channelId,
                'type'         => $subscribe->type,
                'email'        => $subscribe->email,
                'is_confirmed' => $subscribe->isConfirmed,
            ]
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        //$this->result = isset($data['confirmed']) ? $data : [];
        $this->result = $data;
    }
}