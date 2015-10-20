<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetDescriptionListByUiList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $uis
     * @param array $filter
     * @param string|null $regionId
     */
    public function __construct(array $uis, array $filter = [], $regionId = null) {
        $filter += [
            'trustfactor' => false,
            'category'    => false,
            'media'       => false,
            'property'    => false,
            'tag'         => false,
            'seo'         => false,
            'label'       => false,
            'brand'       => false,
        ];

        $this->url = new Url();
        $this->url->path = 'product/get-description/v1';
        $this->url->query = [
            'uids' => $uis,
        ];
        if ($filter['trustfactor']) {
            $this->url->query['trustfactor'] = true;
        }
        if ($filter['category']) {
            $this->url->query['category'] = true;
        }
        if ($filter['media']) {
            $this->url->query['media'] = true;
        }
        if ($filter['property']) {
            $this->url->query['property'] = true;
        }
        if ($filter['tag']) {
            $this->url->query['tag'] = true;
        }
        if ($filter['seo']) {
            $this->url->query['seo'] = true;
        }
        if ($filter['label']) {
            $this->url->query['label'] = true;
        }
        if ($filter['brand']) {
            $this->url->query['brand'] = true;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['products']) ? $data['products'] : [];
    }
}