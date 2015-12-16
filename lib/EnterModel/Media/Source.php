<?php
namespace EnterModel\Media;

class Source {
    /** @var string */
    public $type;
    /** @var string */
    public $url;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (isset($data['type'])) $this->type = (string)$data['type'];
        if (isset($data['url'])) $this->url = (string)$data['url'];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        if (isset($data['type'])) $this->type =  $data['type'];
        if (isset($data['url'])) $this->url =  $data['url'];
    }
}