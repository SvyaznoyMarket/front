<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetUiPager extends Query {
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
            'product_uid' => 1, // SPPX-202
            'filter'      => [
                'filters' => $filterData,
                'sort'    => $sortingData,
                'offset'  => $offset,
                'limit'   => $limit,
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

        $this->result = (isset($data['list']) && isset($data['count'])) ? $data : [];
    }
}