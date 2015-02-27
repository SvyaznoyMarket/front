<?php

namespace EnterQuery\Product\Filter;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $filterData
     * @param string|null $regionId
     */
    public function __construct(array $filterData, $regionId = null) {
        // MSITE-132 Временно исключить из выдачи сайта партнёрские товары-слоты
        $filterData[] = ['exclude_partner_type', 1, \EnterModel\Product::PARTNER_OFFER_TYPE_SLOT];

        $this->url = new Url();
        $this->url->path = 'v2/listing/filter';
        $this->url->query['filter'] = [
            'filters' => $filterData,
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