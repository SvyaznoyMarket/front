<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class SendToSelection extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $orderNumber
     * @param string $shopId
     */
    public function __construct($orderNumber, $shopId) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'private/erp-integration/send-for-package';
        $this->data = [
            'order_number' => $orderNumber,
            'shop_id'      => $shopId,
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
