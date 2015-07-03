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
     * @param array $view
     */
    public function __construct(array $ids, $regionId, $view = []) {
        $this->url = new Url();
        $this->url->path = 'v2/product/get-v3';
        $this->url->query = [
            'select_type' => 'id',
            'id'          => $ids,
        ];
        if ($regionId) {
            $this->url->query['geo_id'] = $regionId;
        }
        if (false === $view['model']) {
            $this->url->query['withModels'] = 0;
        }
        if (false === $view['related']) {
            $this->url->query['withRelated'] = 0;
        }
        if (false === $view['availability']) {
            $this->url->query['getCoreAvailability'] = 0;
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