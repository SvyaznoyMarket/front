<?php

namespace EnterQuery\Event;

use Enter\Curl\Query;
use EnterQuery\EventQueryTrait;
use EnterQuery\Url;

class GetProductView extends Query {
    use EventQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $userUi
     */
    public function __construct($userUi = null) {
        $this->url = new Url();
        $this->url->path = 'product/view';
        if ($userUi) {
            $this->url->query['user_uid'] = $userUi;
        }
        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}