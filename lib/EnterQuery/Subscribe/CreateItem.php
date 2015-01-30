<?php

namespace EnterQuery\Subscribe;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class CreateItem extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result = [];

    /**
     * @param Model\Subscribe $subscribe
     * @param string|null $userToken
     */
    public function __construct($subscribe, $userToken = null) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/subscribe/create';

        if ($userToken) {
            $this->url->query['token'] = $userToken;
        }

        $this->data[] = [
            'channel_id'   => $subscribe->channelId,
            //'type'         => $subscribe->type,
            'email'        => $subscribe->email,
            //'is_confirmed' => $subscribe->isConfirmed,
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