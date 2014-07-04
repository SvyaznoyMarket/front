<?php

namespace EnterSite\Curl\Query\Search;

use Enter\Curl\Query;
use EnterSite\Curl\Query\CoreQueryTrait;
use EnterSite\Model;
use EnterSite\Curl\Query\Url;

class GetAutocompleteResultByPhrase extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phrase
     * @param string $regionId
     */
    public function __construct($phrase, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/search/autocomplete';
        $this->url->query = [
            'letters'  => $phrase,
            'region_id'   => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = $data;
    }
}
