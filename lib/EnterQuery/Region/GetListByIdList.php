<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetListByIdList extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $ids
     */
    public function __construct($ids) {
        $this->url = new Url();
        $this->url->path = 'api/geo/get-town';
        $this->url->query = [
            'id' => $ids,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = is_array($data['result']) ? $data['result'] : [];
    }
}