<?php

namespace EnterModel\Product\Media;

class Photo {
    /** @var string */
    public $id;
    /** @var string */
    public $source;
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('source', $data)) $this->source = (string)$data['source'];
        if (array_key_exists('width', $data)) $this->width = (int)$data['width'];
        if (array_key_exists('height', $data)) $this->height = (int)$data['height'];
    }
}