<?php

namespace EnterQuery\MainMenu;

use Enter\Curl\Query;
use EnterQuery\CmsQueryTrait;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItem extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string|null $shopUi
     */
    public function __construct($shopUi = null) {
        $this->url = new Url();
        $this->url->path = 'seo/main-menu';
        if ($shopUi) {
            $this->url->query['shop_ui'] = $shopUi;
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