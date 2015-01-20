<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByIdList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $ids
     * @param string $regionId
     */
    public function __construct(array $ids, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/gets';
        $this->url->query = [
            'ids'    => $ids,
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