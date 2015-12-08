<?php

namespace EnterQuery\Partner;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetTrafficSource extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    public function __construct() {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'api/traffic-source';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result']) && is_array($data['result']) ? $data['result'] : [];
    }
}