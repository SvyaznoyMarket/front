<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetDescriptionItemByUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @deprecated
     * @param string $ui
     */
    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'product/get-description';
        $this->url->query = [
            'uid' => $ui,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['uid']) ? $data : null;
    }
}