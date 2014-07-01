<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Media {
    /** @var Model\Product\Media\Photo[] */
    public $photos = [];
    /** @var Model\Product\Media\Photo3d[] */
    public $photo3ds = [];
    /** @var Model\Product\Media\Video[] */
    public $videos = [];
}