<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByUiList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $uis
     * @param string $regionId
     */
    public function __construct(array $uis, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/gets';
        $this->url->query = [
            'uids'   => $uis,
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['categories'][0]) ? $data['categories'] : [];
    }
}