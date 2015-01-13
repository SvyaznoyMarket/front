<?php

namespace EnterQuery\Product\Slice;

use Enter\Curl\Query;
use EnterQuery\CmsQueryTrait;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItemByToken extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($token) {
        $this->url = new Url();
        $this->url->path = 'seo/get-slice';
        $this->url->query['url'] = $token;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['name']) ? $data : null;
    }
}