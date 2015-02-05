<?php

namespace EnterQuery\SpecialPage;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetListByTokenList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result = [];

    /**
     * @param $tokens
     */
    public function __construct($tokens) {
        $this->url = new Url();
        $this->url->path = 'seo/special-page';
        $this->url->query = [
            'name' => $tokens,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['special_pages']) ? $data : [];
    }
}