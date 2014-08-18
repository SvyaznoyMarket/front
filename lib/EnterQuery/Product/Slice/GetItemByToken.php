<?php

namespace EnterQuery\Product\Slice;

use Enter\Curl\Query;
use EnterQuery\CmsQueryTrait;
use EnterQuery\Url;

class GetItemByToken extends Query {
    use CmsQueryTrait;

    /** @var array|null */
    protected $result;
    /** @var array */
    private $parameters = [];

    public function __construct($token) {
        $this->parameters['token'] = $token;

        $this->url = new Url();
        $this->url->path = 'v1/slice/' . $token . '.json';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['name']) ? array_merge(['token' => $this->parameters['token']], $data) : null;
    }
}