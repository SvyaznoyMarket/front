<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemById extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result = [];

    /**
     * @param string $id
     * @param string $regionId
     */
    public function __construct($id, $regionId = null) {
        $this->url = new Url();
        $this->url->path = 'v2/product/get-v3';
        $this->url->query = [
            'select_type' => 'id',
            'id'          => $id,
            'withModels'  => 0,
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

        if (isset($data[0]['id'])) {
            // MAPI-95
            foreach ($data as $key => $item) {
                unset($data[$key]['category']);
            }

            $this->result = $data[0];
        }
    }
}