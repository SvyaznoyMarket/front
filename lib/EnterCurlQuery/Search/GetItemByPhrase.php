<?php

namespace EnterCurlQuery\Search;

use Enter\Curl\Query;
use EnterCurlQuery\CoreQueryTrait;
use EnterModel as Model;
use EnterCurlQuery\Url;

class GetItemByPhrase extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phrase
     * @param array $filterData
     * @param Model\Product\Sorting $sorting
     * @param string|null $regionId
     * @param int|null $offset
     * @param int|null $limit
     */
    public function __construct($phrase, array $filterData, Model\Product\Sorting $sorting = null, $regionId = null, $offset = null, $limit = null) {
        $this->url = new Url();
        $this->url->path = 'v2/search/get';
        $this->url->query = [
            'request'  => $phrase,
            'geo_id'   => $regionId,
            'start'    => $offset,
            'limit'    => $limit,
            'use_mean' => true,
        ];

        if ((bool)$filterData) {
            $this->url->query['filter'] = [
                'filters' => $filterData,
            ];
        }

        if ($sorting) {
            $this->url->query['product'] = ['sort' => [$sorting->token => $sorting->direction]];
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['1']['count']) ? $data : null;
    }
}