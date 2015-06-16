<?php

namespace EnterQuery\AbTest;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetActiveList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'api/ab_test/get-active';
        $this->url->query = [
            'tags' => ['site-mobile'],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['uid']) ? $data['result'] : [];
    }
}