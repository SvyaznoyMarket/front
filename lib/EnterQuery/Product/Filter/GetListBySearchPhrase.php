<?php

namespace EnterQuery\Product\Filter;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
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
                    ['exclude_partner_type', 1, \EnterModel\Product::PARTNER_OFFER_TYPE_SLOT], // MSITE-132 Временно исключить из выдачи сайта партнёрские товары-слоты
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