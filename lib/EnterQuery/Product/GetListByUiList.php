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
        $view += ['model' => true, 'related' => true, 'availability' => true];

        $this->url = new Url();
        $this->url->path = 'v2/product/get-v3';
        $this->url->query = [
            'select_type' => 'ui',
            'ui'          => $uis,
        ];
        if ($view['model']) {
            $this->url->query['withModels'] = true;
        }
        if ($view['related']) {
            $this->url->query['withRelated'] = true;
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