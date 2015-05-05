<?php

namespace EnterQuery\MainMenu;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItem extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string|null $shopUi
     * @param array|null $tags
     */
    public function __construct($shopUi = null, array $tags = null) {
        $this->url = new Url();
        $this->url->path = 'seo/main-menu';
        if ($shopUi) {
            $this->url->query['shop_ui'] = $shopUi;
        }
        if ((null === $tags) && isset($this->getConfig()->applicationTags[0])) {
            $tags = [$this->getConfig()->applicationTags[0]];
        }

        if ((bool)$tags) {
            $this->url->query['tags'] = array_filter($tags, function($tag) { return is_string($tag); });
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['item'][0]) ? $data : null;
    }
}