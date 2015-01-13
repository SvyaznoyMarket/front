<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByCoordinates extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $latitude
     * @param $longitude
     */
    public function __construct($latitude, $longitude) {
        $this->url = new Url();
        $this->url->path = 'v2/geo/locate';
        $this->url->query = [
            'coord' => [
                'lat'  => $latitude,
                'long' => $longitude,
            ],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['id']) ? [$data] : [];
    }
}