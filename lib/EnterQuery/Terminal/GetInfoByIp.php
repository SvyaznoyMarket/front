<?php

namespace EnterQuery\Terminal;

use Enter\Curl\Query;
use EnterQuery\InfoQueryTrait;
use EnterQuery\Url;

class GetInfoByIp extends Query {
    use InfoQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $ip
     */
    public function __construct($ip) {
        $this->url = new Url();
        $this->url->path = 'terminal/getInfo/ip/' . $ip;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}
