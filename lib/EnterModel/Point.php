<?php

namespace EnterModel;

use EnterModel as Model;

class Point {
    const TYPE_SHOP = 'shop';
    const TYPE_PICKPOINT = 'pickpoint';
    const TYPE_SVYAZNOY = 'svyaznoy';
    const TYPE_HERMES = 'hermes';

    /** @var Point\Group */
    public $group;
    /** @var int */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $address;
    /** @var Model\Region|null */
    public $region;
    /** @var Model\Subway|null */
    public $subway;
    /** @var string */
    public $type;

    /**
     * @param mixed $data
     */
    function __construct($data = []) {
        if (isset($data['partner'])) $this->group = new Point\Group(['slug' => (string)$data['partner']]); // Приходит из https://scms.enter.ru/api/point/get
        if (isset($data['id'])) $this->id = (string)$data['id'];
        if (isset($data['ui'])) $this->ui = (string)$data['ui'];
        if (isset($data['uid'])) $this->ui = (string)$data['uid']; // Приходит из https://scms.enter.ru/api/point/get
        if (isset($data['name'])) $this->name = (string)$data['name'];
        if (isset($data['address'])) $this->address = (string)$data['address'];
        if (isset($data['geo'])) $this->region = new Model\Region($data['geo']);
        if (isset($data['type'])) $this->type = (string)$data['type'];
        if (isset($data['subway']['name'])) $this->subway = new Model\Subway($data['subway']);
    }
}