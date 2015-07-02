<?php

namespace EnterQuery\Shop;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

/**
 * @deprecated Используйте \EnterQuery\Point\GetItemByUi
 */
class GetItemByUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $ui
     */
    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'shop/get';
        $this->url->query = [
            'uid' => [$ui],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['uid']) ? $data['result'][0] : null;
    }
}