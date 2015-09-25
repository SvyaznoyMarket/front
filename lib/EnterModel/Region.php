<?php

namespace EnterModel;

use EnterModel as Model;

class Region {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $kladrId;
    /** @var string */
    public $code;
    /** @var string */
    public $parentId;
    /** @var string */
    public $name;
    /** @var string */
    public $token;
    /** @var float */
    public $latitude;
    /** @var float */
    public $longitude;
    /** @var bool */
    public $transportCompanyAvailable;
    /** @var Model\Region|null */
    public $parent;
    /** @var int */
    public $pointCount = 0;
    /** @var Model\Region\InflectedName|null */
    public $inflectedName;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('kladr_id', $data)) $this->kladrId = !empty($data['kladr_id'])
            ? substr((string)$data['kladr_id'], 0, 13) // согласно спецификации КЛАДР код населенного пункта состоит из 13 символов
            : null;
        if (array_key_exists('code', $data)) $this->code = (string)$data['code'];
        if (array_key_exists('parent_id', $data)) $this->parentId = (string)$data['parent_id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('slug', $data)) {
            $this->token = (string)$data['slug'];
        } else if (array_key_exists('token', $data)) {
            $this->token = (string)$data['token']; // FIXME: deprecated
        }
        if (array_key_exists('coord_long', $data)) $this->longitude = (float)$data['coord_long'];
        if (array_key_exists('coord_lat', $data)) $this->latitude = (float)$data['coord_lat'];
        if (isset($data['location']['longitude'])) $this->longitude = (float)$data['location']['longitude'];
        if (isset($data['location']['latitude'])) $this->latitude = (float)$data['location']['latitude'];
        if (array_key_exists('tk_available', $data)) $this->transportCompanyAvailable = (bool)$data['tk_available'];
        if (isset($data['number_of_enter_shops'])) {
            $this->pointCount += $data['number_of_enter_shops'];
        };
        if (isset($data['number_of_pickup_points'])) {
            $this->pointCount += $data['number_of_pickup_points'];
        };

        if (array_key_exists('name_inflect', $data) && is_array($data['name_inflect'])) {
            $this->inflectedName = new Model\Region\InflectedName($data['name_inflect']);
        }
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        if (isset($data['name'])) $this->name = (string)$data['name'];
    }
}