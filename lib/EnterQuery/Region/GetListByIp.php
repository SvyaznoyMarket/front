<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByIp extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $ip
     */
    public function __construct($ip) {
        $this->url = new Url();
        $this->url->path = 'v2/geo/locate';
        $this->url->query = [
            'ip' => $ip,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['id']) ? [$data] : [];
    }
}