<?php

namespace EnterQuery\MainMenu;

use Enter\Curl\Query;
use EnterQuery\CmsQueryTrait;
use EnterQuery\Url;

class GetList extends Query {
    use CmsQueryTrait;

    /** @var array */
    protected $result;

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'v2/partner/paid-source.json';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = is_array($data) ? $data : [];
    }
}