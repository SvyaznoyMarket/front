<?php

namespace EnterQuery\Terminal;

use Enter\Curl\Query;
use EnterQuery\InfoQueryTrait;
use EnterQuery\Url;

class GetInfoByUi extends Query {
    use InfoQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $ui
     */
    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'terminal/getInfo/ui/' . $ui;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data['shop_id']) ? $data : null;
    }
}
