<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByIdList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $ids
     * @param string $regionId
     */
    public function __construct(array $ids, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/product/get';
        $this->url->query = [
            'select_type' => 'id',
            'id'          => $ids,
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

        $this->result = isset($data[0]['id']) ? $data : [];
    }
}