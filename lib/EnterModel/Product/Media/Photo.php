<?php

namespace EnterModel\Product\Media;

class Photo {
    /** @var string */
    public $id;
    /** @var string */
    public $source;
    /** @var string */
    public $contentType;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('source', $data)) $this->source = (string)$data['source'];
        if ($this->source && $extension = pathinfo($this->source, PATHINFO_EXTENSION)) {
            $this->contentType = 'image/' . $extension;
        }
    }
}