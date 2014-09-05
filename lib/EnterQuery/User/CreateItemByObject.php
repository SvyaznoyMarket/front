<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterModel as Model;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

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
            'geo_id'     => $user->regionId,
        ];
        if ($user->email) {
            $this->data['email'] = $user->email;
        }
        if ($user->phone) {
            $this->data['mobile'] = $user->phone;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = (isset($data['id']) || isset($data['ui'])) ? $data : null;
    }
}