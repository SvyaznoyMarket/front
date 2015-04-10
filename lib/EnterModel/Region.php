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
    public $wikimartId;
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

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('kladr_id', $data)) $this->kladrId = !empty($data['kladr_id']) ? (string)$data['kladr_id'] : null;
        if (array_key_exists('wikimart_id', $data)) $this->wikimartId = (string)$data['wikimart_id'];
        if (array_key_exists('code', $data)) $this->code = (string)$data['code'];
        if (array_key_exists('parent_id', $data)) $this->parentId = (string)$data['parent_id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token']; // FIXME: deprecated
        if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];

        if (array_key_exists('coord_long', $data)) $this->longitude = (float)$data['coord_long'];
        if (array_key_exists('coord_lat', $data)) $this->latitude = (float)$data['coord_lat'];
        if (isset($data['location']['longitude'])) $this->longitude = (float)$data['location']['longitude'];
        if (isset($data['location']['latitude'])) $this->latitude = (float)$data['location']['latitude'];

        if (array_key_exists('tk_available', $data)) $this->transportCompanyAvailable = (bool)$data['tk_available'];
    }
}