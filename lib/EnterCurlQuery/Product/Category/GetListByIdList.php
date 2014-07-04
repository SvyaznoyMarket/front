<?php

namespace EnterCurlQuery\Product\Category;

use Enter\Curl\Query;
use EnterCurlQuery\CoreQueryTrait;
use EnterCurlQuery\Url;
use EnterModel as Model;

class GetListByIdList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $ids
     * @param string $regionId
     */
    public function __construct(array $ids, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/category/get';
        $this->url->query = [
            'id'     => $ids,
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]) ? $data : [];
    }
}