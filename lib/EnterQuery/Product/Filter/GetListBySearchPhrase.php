<?php

namespace EnterQuery\Product\Filter;

use Enter\Curl\Query;
use EnterQuery\SearchQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListBySearchPhrase extends Query {
    use SearchQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $searchPhrase
     * @param string|null $regionId
     */
    public function __construct($searchPhrase, $regionId = null) {
        $this->url = new Url();
        $this->url->path = 'listing/filter';
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

        $this->url->query['filter']['filters'][] = ['exclude_partner_type', 1, 2]; // AG-59 Временная заглушка для отключения кухонь

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