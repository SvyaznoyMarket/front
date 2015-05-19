<?php

namespace EnterModel;

class Address {
    const TYPE_UNDEFINED = 0;
    const TYPE_HOME = 1;
    const TYPE_WORK = 2;

    /** @var string */
    public $id;
    /** @var string */
    public $userUi;
    /** @var int */
    public $type;
    /** @var string */
    public $kladrId;
    /** @var string */
    public $regionId;
    /** @var string */
    public $zipCode;
    /** @var string */
    public $street;
    /** @var string */
    public $streetType;
    /** @var string */
    public $building;
    /** @var string */
    public $apartment;
    /** @var string */
    public $description;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('user_uid', $data)) $this->userUi = (string)$data['user_uid'];
        if (array_key_exists('type', $data)) $this->type = (int)$data['type'];
        if (array_key_exists('kladr_id', $data)) $this->kladrId = (string)$data['kladr_id'];
        if (array_key_exists('geo_id', $data)) $this->regionId = (string)$data['geo_id'];
        if (array_key_exists('zip_code', $data)) $this->zipCode = (string)$data['zip_code'];
        if (array_key_exists('street', $data)) $this->street = (string)$data['street'];
        if (array_key_exists('street_type', $data)) $this->streetType = (string)$data['street_type'];
        if (array_key_exists('building', $data)) $this->building = (string)$data['building'];
        if (array_key_exists('apartment', $data)) $this->apartment = (string)$data['apartment'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
    }
}