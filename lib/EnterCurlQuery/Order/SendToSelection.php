<?php

namespace EnterCurlQuery\Order;

use Enter\Curl\Query;
use EnterCurlQuery\CoreQueryTrait;
use EnterCurlQuery\Url;

class SendToSelection extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $orderNumber
     * @param string $shopId
     */
    public function __construct($orderNumber, $shopId) {
        $this->url = new Url();
        $this->url->path = 'private/erp-integration/send-for-package';
        $this->data = [
            'order_number' => $orderNumber,
            'shop_id' => $shopId,
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
