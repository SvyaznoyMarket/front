<?php

namespace EnterQuery\Promo;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $tags
     */
    public function __construct(array $tags = []) {
        $this->url = new Url();
        $this->url->path = 'api/promo/get';

        if ($tags) {
            $this->url->query = [
                'tags' => $tags,
            ];
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['uid']) ? $data['result'] : [];
    }
}