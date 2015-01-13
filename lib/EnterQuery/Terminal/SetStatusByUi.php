<?php

namespace EnterQuery\Terminal;

use Enter\Curl\Query;
use EnterQuery\InfoQueryTrait;
use EnterQuery\Url;

class SetStatusByUi extends Query {
    use InfoQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $ui
     * @param array $data
     */
    public function __construct($ui, array $data) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'terminal/setStatus/ui/' . $ui;
        $this->data = $data;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}
