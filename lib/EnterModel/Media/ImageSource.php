<?php
namespace EnterModel\Media;

class ImageSource extends Source {
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        parent::__construct($data);

        if (isset($data['width'])) $this->width = (string)$data['width'];
        if (isset($data['height'])) $this->height = (string)$data['height'];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        parent::fromArray($data);
        if (isset($data['width'])) $this->width =  $data['width'];
        if (isset($data['height'])) $this->height =  $data['height'];
    }
}