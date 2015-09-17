<?php

namespace EnterQuery\Search;

use Enter\Curl\Query;
use EnterModel as Model;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetAutocompleteResultByPhrase extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phrase
     * @param string $regionId
     */
    public function __construct($phrase, $regionId) {
        $this->url = new Url();
        $this->url->path = 'api/search/autocomplete';
        $this->url->query = [
            'request'  => $phrase,
            'geo_town_id'   => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}