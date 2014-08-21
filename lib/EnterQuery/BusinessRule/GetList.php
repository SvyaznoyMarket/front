<?php

namespace EnterQuery\BusinessRule;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'private/site-integration/get-business-rules';
        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data['detail']) ? $data['detail'] : null;
    }
}