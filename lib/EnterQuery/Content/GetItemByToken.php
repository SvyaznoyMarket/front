<?php

namespace EnterQuery\Content;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItemByToken extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $token
     * @param bool $showSidebar
     */
    public function __construct($token) {
        $this->url = new Url();
        $this->url->path = 'api/static-page';
        $this->url->query = [
            'token' => [$token],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['pages'][0]) ? $data['pages'][0] : null;
    }
}