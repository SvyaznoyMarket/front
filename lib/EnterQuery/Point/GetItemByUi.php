<?php

namespace EnterQuery\Point;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $ui
     */
    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'api/point/get';
        $this->url->query = [
            'uids' => [$ui],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $point = isset($data['points'][0]['uid']) ? $data['points'][0] : null;

        if (isset($data['partners'][0]) && isset($point['partner'])) {
            foreach ($data['partners'] as $partner) {
                if ($partner['slug'] === $point['partner']) {
                    $point['partner'] = $partner;
                    break;
                }
            }
        }

        $this->result = $point;
    }
}