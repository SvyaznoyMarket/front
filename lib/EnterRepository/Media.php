<?php

namespace EnterRepository;

use Enter\Http;
use EnterModel as Model;

class Media {
    /**
     * @param Model\Media[] $mediaList
     * @param string $mediaTag
     * * @param string $sourceType
     * @return Model\Media\Source
     */
    public function getSourceObjectByList(array $mediaList, $mediaTag, $sourceType) {
        foreach ($mediaList as $media) {
            if (in_array($mediaTag, $media->tags, true)) {
                foreach ($media->sources as $source) {
                    if ($source->type === $sourceType) {
                        return $source;
                    }
                }
            }
        }

        return new Model\Media\Source();
    }

    /**
     * * @param string $sourceType
     * @return Model\Media\Source
     */
    public function getSourceObjectByItem(Model\Media $media, $sourceType) {
        foreach ($media->sources as $source) {
            if ($source->type === $sourceType) {
                return $source;
            }
        }

        return new Model\Media\Source();
    }
}