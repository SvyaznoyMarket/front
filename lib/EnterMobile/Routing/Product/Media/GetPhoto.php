<?php

namespace EnterMobile\Routing\Product\Media;

use EnterAggregator\LoggerTrait;
use EnterMobile\Routing\Route;
use EnterMobile\Config;
use EnterModel as Model;

class GetPhoto extends Route {
    use LoggerTrait;

    /** @var Model\Media */
    private $photo;
    /** @var string */
    private $size;

    public function __construct(Model\Media $photo, $size) {
        $this->photo = $photo;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function __toString() {
        try {
            foreach ($this->photo->sources as $source) {
                if ($source->type != $this->size) continue;

                return $source->url;
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['route']]);
        }

        return '';
    }
}