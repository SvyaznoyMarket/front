<?php

namespace EnterQuery\Brand;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemById extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $id
     * @param string|null $regionId
     */
    public function __construct($id, $regionId = null) {
        $this->url = new Url();
        $this->url->path = 'v2/brand/get';
        $this->url->query = [
            'id' => $id,
        ];
        if ($regionId) {
            $this->url->query['geo_id'] = $regionId;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['id']) ? $data[0] : null;
    }
}