<?php

namespace EnterQuery\Content;

use Enter\Curl\Query;
use EnterQuery\ContentQueryTrait;
use EnterQuery\Url;

class GetItemByToken extends Query {
    use ContentQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $token
     */
    public function __construct($token, $showSidebar = true) {
        $this->url = new Url();
        $this->url->path = $token;
        $this->url->query = [
            'show_sidebar' => (int)$showSidebar,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['content']) ? $data : null;
    }
}