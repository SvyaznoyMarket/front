<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemById extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $id
     * @param string $regionId
     * @param array $view
     */
    public function __construct($id, $regionId = null, $view = []) {
        $view = array_merge(['model' => true, 'related' => true], $view);

        $this->url = new Url();
        $this->url->path = 'v2/product/get-v3';
        $this->url->query = [
            'select_type' => 'id',
            'id'          => $id,
        ];
        if (false === $view['model']) {
            $this->url->query['withModels'] = 0;
        }
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

        $this->result = isset($data[0]['id']) ? $data[0] : null;
    }
}