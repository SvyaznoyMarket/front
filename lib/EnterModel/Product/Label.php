<?php

namespace EnterModel\Product;

class Label {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var \EnterModel\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];

        $this->media = new \EnterModel\MediaList(isset($data['medias']) ? $data['medias'] : []);
    }
}