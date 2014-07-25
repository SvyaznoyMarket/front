<?php

namespace Enter\Http\Session;

use Enter\Http;

class FlashBag {
    /** @var array */
    private $content = [
        'new' => [],
        'old' => [],
    ];

    /**
     * @param $data
     */
    public function __construct($data) {
        if (isset($data['new']) && is_array($data['new'])) {
            $this->content['new'] = $data['new'];
        }
        if (isset($data['old']) && is_array($data['old'])) {
            $this->content['old'] = $data['old'];
        }
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value) {
        $this->content['new'][$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key) {
        $value = null;

        if (array_key_exists($key, $this->content['old'])) {
            $value = $this->content['old'][$key];
        } else if (array_key_exists($key, $this->content['new'])) {
            $value = $this->content['new'][$key];
        }

        return $value;
    }

    public function renew() {
        $this->content['old'] = [];
        $this->content['old'] = $this->content['new'];
        $this->content['new'] = [];
    }

    /**
     * @return array
     */
    public function dump() {
        $data = [];

        if ((bool)$this->content['new']) {
            $data['new'] = $this->content['new'];
        }
        if ((bool)$this->content['old']) {
            $data['old'] = $this->content['old'];
        }

        return $data;
    }
}