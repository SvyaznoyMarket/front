<?php

namespace EnterModel\Region;

class InflectedName {
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $nominativus;
    /** @var string */
    public $genitivus;
    /** @var string */
    public $dativus;
    /** @var string */
    public $accusativus;
    /** @var string */
    public $ablativus;
    /** @var string */
    public $locativus;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('nominativus', $data)) $this->nominativus = (string)$data['nominativus'];
        if (array_key_exists('genitivus', $data)) $this->genitivus = (string)$data['genitivus'];
        if (array_key_exists('dativus', $data)) $this->dativus = (string)$data['dativus'];
        if (array_key_exists('accusativus', $data)) $this->accusativus = (string)$data['accusativus'];
        if (array_key_exists('ablativus', $data)) $this->ablativus = (string)$data['ablativus'];
        if (array_key_exists('locativus', $data)) $this->locativus = (string)$data['locativus'];
    }
}