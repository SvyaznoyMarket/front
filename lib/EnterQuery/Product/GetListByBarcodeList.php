<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByBarcodeList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $barcodes
     * @param string $regionId
     */
    public function __construct(array $barcodes, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/product/get';
        $this->url->query = [
            'select_type' => 'bar_code',
            'bar_code'    => $barcodes,
        ];
        if ($regionId) {
            $this->url->query['geo_id'] = $regionId;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['id']) ? $data : [];
    }
}