<?php

namespace EnterModel;

use EnterModel as Model;

class User {
    /** @var string */
    public $id;
    /** @var string */
    public $firstName;
    /** @var string */
    public $lastName;
    /** @var string */
    public $middleName;
    /** @var int|null */
    public $sex;
    /** @var string */
    public $phone;
    /** @var string|null */
    public $homePhone;
    /** @var string */
    public $email;
    /** @var string|null */
    public $occupation;
    /** @var string|null */
    public $birthday;
    /** @var string|null */
    public $svyaznoyClubCardNumber;
    /** @var string */
    public $regionId;
    /** @var Model\Region|null */
    public $region;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (!empty($data['id'])) $this->id = (string)$data['id'];
        if (!empty($data['first_name'])) $this->firstName = (string)$data['first_name'];
        if (!empty($data['last_name'])) $this->lastName = (string)$data['last_name'];
        if (!empty($data['middle_name'])) $this->middleName = (string)$data['middle_name'];
        if (!empty($data['sex'])) $this->sex = (int)$data['sex'];
        if (!empty($data['email'])) $this->email = (string)$data['email'];
        if (!empty($data['mobile'])) $this->phone = (string)$data['mobile'];
        if (!empty($data['phone'])) $this->homePhone = (string)$data['phone'];
        if (!empty($data['geo_id'])) $this->regionId = (string)$data['geo_id'];
        if (!empty($data['birthday'])) $this->birthday = (string)$data['birthday'];
        if (!empty($data['occupation'])) $this->occupation = (string)$data['occupation'];
        if (!empty($data['svyaznoy_club_card_number'])) $this->svyaznoyClubCardNumber = (string)$data['svyaznoy_club_card_number'];

        if (isset($data['geo']['id'])) $this->region = new Model\Region($data['geo']);
    }
}