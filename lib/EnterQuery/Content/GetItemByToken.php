<?php

namespace EnterQuery\Content;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItemByToken extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $token
     * @param int|string $regionId
     * @param array $tags
     */
    public function __construct($token, $regionId, array $tags = []) {
        $this->url = new Url();
        $this->url->path = 'api/static-page';
        $this->url->query = [
            'token' => [$token],
            'geo_town_id' => $regionId,
        ];

        if ($tags) {
            $this->url->query['tags'] = $tags;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['pages'][0]) ? $data['pages'][0] : null;
    }
}