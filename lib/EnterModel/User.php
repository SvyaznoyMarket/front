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
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('first_name', $data)) $this->firstName = $data['first_name'] ? (string)$data['first_name'] : $data['first_name'];
        if (array_key_exists('last_name', $data)) $this->lastName = $data['last_name'] ? (string)$data['last_name'] : $data['last_name'];
        if (array_key_exists('middle_name', $data)) $this->middleName = $data['middle_name'] ? (string)$data['middle_name'] : null;
        if (array_key_exists('sex', $data)) $this->sex = $data['sex'] ? (int)$data['sex'] : null;
        if (array_key_exists('email', $data)) $this->email = (string)$data['email'];
        if (array_key_exists('mobile', $data)) $this->phone = (string)$data['mobile'];
        if (array_key_exists('phone', $data)) $this->homePhone = $data['phone'] ? (string)$data['phone'] : null;
        if (array_key_exists('geo_id', $data)) $this->regionId = (string)$data['geo_id'];
        if (array_key_exists('birthday', $data)) $this->birthday = $data['birthday'] ? (string)$data['birthday'] : null;
        if (array_key_exists('occupation', $data)) $this->occupation = $data['occupation'] ? (string)$data['occupation'] : null;
        if (array_key_exists('svyaznoy_club_card_number', $data)) $this->svyaznoyClubCardNumber = $data['svyaznoy_club_card_number'] ? (string)$data['svyaznoy_club_card_number'] : null;

        if (isset($data['geo']['id'])) $this->region = new Model\Region($data['geo']);
    }
}