<?php

namespace EnterModel\Product\Media;

use EnterAggregator\ConfigTrait;

class Photo {
    use ConfigTrait; // FIXME!!!

    /** @var string */
    public $id;
    /** @var string */
    public $source;
    /** @var string */
    public $contentType;
    /** @var string */
    public $host;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('source', $data)) $this->source = (string)$data['source'];
        if ($this->source && $extension = pathinfo($this->source, PATHINFO_EXTENSION)) {
            $this->contentType = 'image/' . $extension;

            $hosts = $this->getConfig()->mediaHosts;
            $index = !empty($this->id) ? ($this->id % count($hosts)) : rand(0, count($hosts) - 1);

            $this->host =  isset($hosts[$index]) ? $hosts[$index] : null;
        }
    }
}