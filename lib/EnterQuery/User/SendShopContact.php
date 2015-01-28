<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class SendShopContact extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $shopId
     * @param string $email
     */
    public function __construct($shopId, $email) {
        $this->url = new Url();
        $this->url->path = '/v2/notifications/send-shop-contacts';
        $this->url->query = [
            'shop_id' => $shopId,
            'email'   => $email,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result']) ? $data['result'] : null;
    }
}