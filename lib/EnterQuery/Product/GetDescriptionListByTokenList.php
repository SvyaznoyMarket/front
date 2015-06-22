<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetDescriptionListByTokenList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $tokens
     * @param array $filter
     * @param string|null $regionId
     */
    public function __construct(array $tokens, array $filter = [], $regionId = null) {
        $filter += [
            'trustfactor' => false,
            'media'       => false,
            'category'    => false,
            'label'       => false,
            'brand'       => false,
            'property'    => false,
            'tag'         => false,
            'seo'         => false,
        ];

        $this->url = new Url();
        $this->url->path = 'product/get-description/v1';
        $this->url->query = [
            'slugs' => $tokens,
        ];
        if ($filter['trustfactor']) {
            $this->url->query['trustfactor'] = true;
        }
        if ($filter['media']) {
            $this->url->query['media'] = true;
        }
        if ($filter['category']) {
            $this->url->query['category'] = true;
        }
        if ($filter['label']) {
            $this->url->query['label'] = true;
        }
        if ($filter['brand']) {
            $this->url->query['brand'] = true;
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