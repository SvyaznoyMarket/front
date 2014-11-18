<?php

namespace EnterModel;

class Subscribe {
    /** @var string */
    //public $id;
    /** @var string */
    public $channelId;
    /** @var string */
    public $type;
    /** @var string */
    public $email;
    /** @var bool */
    public $isConfirmed;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        //if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('channel_id', $data)) $this->channelId = (string)$data['channel_id'];
        if (array_key_exists('type', $data)) $this->type = (string)$data['type'];
        if (array_key_exists('email', $data)) $this->email = (string)$data['email'];
        if (array_key_exists('is_confirmed', $data)) $this->isConfirmed = (bool)$data['is_confirmed'];
    }
}