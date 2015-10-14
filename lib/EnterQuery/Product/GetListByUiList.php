<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByUiList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $uis
     * @param string $regionId
     * @param array $view
     */
    public function __construct(array $uis, $regionId, $view = []) {
        $view = array_merge(['related' => true], $view);

        $this->url = new Url();
        $this->url->path = 'v2/product/get-v3';
        $this->url->query = [
            'select_type' => 'ui',
            'ui'          => $uis,
            'withModels'  => 0,
        ];
        if (false === $view['related']) {
            $this->url->query['withRelated'] = 0;
        }
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

        $this->result = isset($data[0]['ui']) ? $data : [];
    }
}