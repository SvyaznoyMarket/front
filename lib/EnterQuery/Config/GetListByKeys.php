<?php

namespace EnterQuery\Config;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetListByKeys extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $keys
     */
    public function __construct($keys) {
        $this->url = new Url();
        $this->url->path = 'api/parameter/get-by-keys';
        $this->url->query = [
            'keys' => $keys,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response) + ['result' => []];

        /*
        $item = null;
        foreach ($data['result'] as &$item) {
            if (!array_key_exists('value', $item)) continue;

            $item['value'] = json_decode($item['value'], true);
        }
        unset($item);
        */

        $this->result = isset($data['result'][0]['key']) ? $data : [];
    }
}