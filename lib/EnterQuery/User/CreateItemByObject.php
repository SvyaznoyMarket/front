<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterModel as Model;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel;

class CreateItemByObject extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\User $user
     */
    public function __construct(Model\User $user) {
        $this->url = new Url();
        $this->url->path = 'v2/user/create';
        $this->data = [
            'first_name' => $user->firstName,
            'email'      => $user->email,
            'mobile'     => $user->phone,
            'geo_id'     => $user->regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['id']) ? $data : null;
    }
}