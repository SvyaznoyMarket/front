<?php
namespace EnterModel;

class Brand {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $url;
    /** @var string */
    public $sliceId;
    /** @var string */
    public $token;
    /** @var \EnterModel\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('url', $data)) $this->url = (string)$data['url'];
        if (array_key_exists('sliceId', $data)) $this->sliceId = (string)$data['sliceId'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];

        $this->media = new \EnterModel\MediaList(isset($data['medias']) ? $data['medias'] : []);
    }
}