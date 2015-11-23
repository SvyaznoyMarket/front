<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterModel as Model;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class CreateProfile extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $token
     * @param array $data
     * @throws \Exception
     */
    public function __construct($token, array $data) {
        $this->retry = 1;

        $data += [
            'userId'      => null,
            'accessToken' => null,
            'type'        => null,
            'email'       => null,
        ];

        $this->url = new Url();
        $this->url->path = 'v2/user/create-account';
        $this->url->query = [
            'token' => $token,
        ];
        switch ($data['type']) {
            case 'fb':
                $this->data['email'] = $data['email'];
                $this->data['fb_id'] = $data['userId'];
                $this->data['fb_access_token'] = $data['accessToken'];
                break;
            case 'vk':
                $this->data['email'] = $data['email'];
                $this->data['vk_id'] = $data['userId'];
                $this->data['vk_access_token'] = $data['accessToken'];
                break;
            default:
                throw new \Exception(sprintf('Неизвестный тип пользователя %s', $data['type']));
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}