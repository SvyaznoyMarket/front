<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class SendNumber extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $orderNumber
     * @param $notificationType
     * @param string|null $phone
     * @param string|null $token
     */
    public function __construct($orderNumber, $notificationType, $phone = null, $token = null) {
        $this->url = new Url();
        $this->url->path = 'v2/order/send-number';
        $this->url->query = [
            'order_number'      => $orderNumber,
            'notification_type' => $notificationType,
        ];
        if ($phone) {
            $this->url->query['phone_number'] = $phone;
        }
        if ($token) {
            $this->url->query['token'] = $token;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}
