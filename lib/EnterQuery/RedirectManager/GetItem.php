<?php

namespace EnterQuery\RedirectManager;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItem extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $fromUrl
     */
    public function __construct($fromUrl) {
        $this->url = new Url();
        $this->url->path = 'seo/redirect';
        $this->url->query = [
            'from_url' => $fromUrl,
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