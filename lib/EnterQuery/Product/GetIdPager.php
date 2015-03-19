<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetIdPager extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $filterData
     * @param Model\Product\Sorting $sorting
     * @param string|null $regionId
     * @param $offset
     * @param $limit
     * @param Model\Product\Category\Config|null $catalogConfig
     */
    public function __construct(array $filterData, Model\Product\Sorting $sorting = null, $regionId = null, $offset = null, $limit = null, $catalogConfig = null) {
        $sortingData = []; // TODO: вынести в Repository\Product\Sorting::dumpObjectList
        if ($sorting) {
            if (('default' == $sorting->token) && $catalogConfig && (bool)$catalogConfig->sortings) {
                // специальная сортировка
                $sortingData = $catalogConfig->sortings;
            } else {
                $sortingData = [$sorting->token => $sorting->direction];
            }
        }

        $this->url = new Url();
        $this->url->path = 'v2/listing/list';
        $this->url->query = [
            'filter' => [
                'filters' => $filterData,
                'sort'    => $sortingData,
                'offset'  => $offset,
                'limit'   => $limit,
            ],
        ];
        if ($regionId) {
            $this->url->query['region_id'] = $regionId;
        }

        // AG-59 Кухни по слотам
        $this->url->query['filter']['filters'][] = ['exclude_partner_type', 1, 2];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = (isset($data['list']) && isset($data['count'])) ? $data : [];
    }
}