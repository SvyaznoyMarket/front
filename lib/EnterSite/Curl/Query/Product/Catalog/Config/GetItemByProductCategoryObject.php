<?php

namespace EnterSite\Curl\Query\Product\Catalog\Config;

use Enter\Curl\Query;
use EnterSite\Curl\Query\CmsQueryTrait;
use EnterSite\Curl\Query\Url;
use EnterSite\Model;

class GetItemByProductCategoryObject extends Query {
    use CmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param \EnterModel\Product\Category[] $categories
     * @param \EnterModel\Product|null $product
     */
    public function __construct(array $categories, \EnterModel\Product $product = null) {
        $tokens = [];
        foreach ($categories as $category) {
            $tokens[] = $category->token;
        }
        if ($product) {
            $tokens[] = $product->token;
        }

        $this->url = new Url();
        $this->url->path = 'v1/catalog/' . implode('/', $tokens) . '.json';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}