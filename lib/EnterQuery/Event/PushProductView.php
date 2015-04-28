<?php

namespace EnterQuery\Event;

use Enter\Curl\Query;
use EnterQuery\EventQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class PushProductView extends Query {
    use EventQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $productUi
     * @param string $userUi
     */
    public function __construct($productUi, $userUi = null) {
        $this->url = new Url();
        $this->url->path = 'product/view';
        $this->data = [
            'product_uid' => $productUi,
        ];
        if ($userUi) {
            $this->data['user_uid'] = $userUi;
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