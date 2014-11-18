<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterModel as Model;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class UpdateItemByObject extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $token
     * @param Model\User $oldUser
     * @param Model\User $newUser
     */
    public function __construct($token, Model\User $oldUser, Model\User $newUser) {
        $this->url = new Url();
        $this->url->path = 'v2/user/update';
        $this->url->query = [
            'token' => $token,
        ];

        $data = [];
        if ($newUser->email !== $oldUser->email) {
            $data['email'] = $newUser->email;
        }
        if ($newUser->phone !== $oldUser->phone) {
            $data['mobile'] = $newUser->phone;
        }
        if ($newUser->firstName !== $oldUser->firstName) {
            $data['first_name'] = $newUser->firstName;
        }
        if ($newUser->lastName !== $oldUser->lastName) {
            $data['last_name'] = $newUser->lastName;
        }
        if ($newUser->middleName !== $oldUser->middleName) {
            $data['middle_name'] = $newUser->middleName;
        }
        if ($newUser->sex !== $oldUser->sex) {
            $data['sex'] = $newUser->sex;
        }
        if ($newUser->birthday !== $oldUser->birthday) {
            $data['birthday'] = $newUser->birthday;
        }
        if ($newUser->occupation !== $oldUser->occupation) {
            $data['occupation'] = $newUser->occupation;
        }
        if ($newUser->homePhone !== $oldUser->homePhone) {
            $data['phone'] = $newUser->homePhone;
        }
        if ($newUser->svyaznoyClubCardNumber !== $oldUser->svyaznoyClubCardNumber) {
            $data['bonus_card'][] = $newUser->svyaznoyClubCardNumber;
        }

        $this->data = $data;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = (isset($data['confirmed']) || isset($data['id'])) ? $data : null;
    }
}