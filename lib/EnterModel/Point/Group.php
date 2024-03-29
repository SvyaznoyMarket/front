<?php

namespace EnterModel\Point;

use EnterModel as Model;

class Group {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $url;

    /**
     * @param mixed $data
     */
    function __construct($data = []) {
        if (isset($data['slug'])) $this->id = (string)$data['slug'];
        if (isset($data['uid'])) $this->ui = (string)$data['uid'];
        if (isset($data['name'])) $this->name = (string)$data['name'];
        if (isset($data['site_url'])) $this->url = (string)$data['site_url'];
    }
}