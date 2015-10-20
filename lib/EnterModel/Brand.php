<?php

namespace EnterModel;

class Brand {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $token;
    /** @var string */
    public $image;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('core_id', $data)) $this->id = (string)$data['core_id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];
        if (array_key_exists('media_image', $data)) $this->image = (string)$data['media_image'];
        if (array_key_exists('medias', $data) && is_array($data['medias'])) {
            foreach ($data['medias'] as $media) {
                $media = new Media($media);
                if ($media->type === 'image' && in_array('small', $media->tags)) {
                    foreach ($media->sources as $source) {
                        if ($source->type === 'original') {
                            $this->image = $source->url;
                        }
                    }
                }
            }
        }

    }
}