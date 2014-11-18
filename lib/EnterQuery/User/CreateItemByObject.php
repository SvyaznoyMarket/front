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
     * @param bool $willBeSubscribed
     */
    public function __construct(Model\User $user, $willBeSubscribed = null) {
        $this->url = new Url();
        $this->url->path = 'v2/user/create';
        $this->data = [
            'first_name' => $user->firstName,
            'geo_id'     => $user->regionId,
        ];
        if ($user->email) {
            $this->data['email'] = $user->email;
            // подписка по email
            if ($willBeSubscribed) {
                $this->data['is_subscribe'] = true;
            }
        }
        if ($user->phone) {
            $this->data['mobile'] = $user->phone;
            // подписка по sms
            if ($willBeSubscribed) {
                $this->data['is_sms_subscribe'] = true;
            }
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