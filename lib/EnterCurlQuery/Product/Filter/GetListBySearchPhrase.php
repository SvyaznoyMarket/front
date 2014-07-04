<?php

namespace EnterCurlQuery\Product\Filter;

use Enter\Curl\Query;
use EnterCurlQuery\CoreQueryTrait;
use EnterCurlQuery\Url;
use EnterModel as Model;

class GetListBySearchPhrase extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $searchPhrase
     * @param string|null $regionId
     */
    public function __construct($searchPhrase, $regionId = null) {
        $this->url = new Url();
        $this->url->path = 'v2/listing/filter';
        $this->url->query = [
            'filter' => [
                'filters' => [
                    ['text', 3, $searchPhrase],
                ],
            ],
        ];
        if ($regionId) {
            $this->url->query['region_id'] = $regionId;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['filter_id']) ? $data : [];
    }
}