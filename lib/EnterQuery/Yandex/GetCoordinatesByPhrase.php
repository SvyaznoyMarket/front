<?php

namespace EnterQuery\Yandex;

use Enter\Curl\Query;
use Enter\Util;
use EnterQuery\Url;

class GetCoordinatesByPhrase extends Query {

    /** @var array|null */
    protected $result;

    /**
     * @param string $phrase
     */
    public function __construct($phrase) {
        $this->retry = 1;
        $this->url = new Url();
        $this->url->path = 'https://geocode-maps.yandex.ru/1.x/';
        $this->url->query = [
            'geocode'  => $phrase,
            'format'   => 'json',
            'results'  => 1
        ];

    }

    /**
     * @param $response
     */
    public function callback($response) {

        $data = null;
        try {
            $data = Util\Json::toArray($response);
        } catch (\Exception $e) {
            $this->error = $e;
        }

        $coordinates = explode(' ', $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);

        $this->result = [
            'latitude' => $coordinates[1],
            'longitude' => $coordinates[0],
        ];
    }
}