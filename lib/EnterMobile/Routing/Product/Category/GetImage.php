<?php

namespace EnterMobile\Routing\Product\Category;

use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class GetImage {
    use LoggerTrait;

    /** @var Model\Product\Category */
    private $category;
    /** @var string */
    private $size;

    /**
     * @param Model\Product\Category $category
     * @param $size
     */
    public function __construct(Model\Product\Category $category, $size) {
        $this->category = $category;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function __toString() {
        try {
            foreach ($this->category->media->photos as $photo) {
                foreach ($photo->sources as $source) {
                    if ($source->type != $this->size) continue;

                    return $source->url;
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['route']]);
        }

        return '';
    }
}