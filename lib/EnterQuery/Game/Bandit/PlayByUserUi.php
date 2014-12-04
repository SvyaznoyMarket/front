<?php

namespace EnterQuery\Game\Bandit;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;

class PlayByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $userUi
     */
    public function __construct($userUi) {
        $this->url = new Url();
        $this->url->path = 'game/bandit/play';
        $this->url->query = [
            'uid' => $userUi,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result']) ? $data : null;
    }
}