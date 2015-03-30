<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Media {
    /** @var Model\Media[] */
    public $photos = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['medias'][0])) {
            foreach ($data['medias'] as $item) {
                $media = new Model\Media($item);

                if ('image' == $media->type) {
                    $this->photos[] = $media;
                }
            }
        }
    }
}