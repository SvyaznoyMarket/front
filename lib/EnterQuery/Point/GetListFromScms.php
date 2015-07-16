<?php

namespace EnterQuery\Point;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListFromScms extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string|null $regionId
     * @param string[] $uis
     */
    public function __construct($regionId = null, $uis = []) {
        $this->url = new Url();
        $this->url->path = 'api/point/get';

        if ($regionId) {
            $this->url->query['geo_id'] = $regionId;
        }

        if ($uis) {
            $this->url->query['uids'] = $uis;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}