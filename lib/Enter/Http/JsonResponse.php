<?php

namespace Enter\Http;

class JsonResponse extends Response {
    /** @var \ArrayObject */
    public $data;
    /** @var int */
    public $encodeOption;

    public function __construct($data = null, $statusCode = self::STATUS_OK) {
        parent::__construct(null, $statusCode);

        $this->encodeOption = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

        $this->data = new \ArrayObject($data ?: []);

        $this->headers['Content-Type'] = 'application/json; charset=' . ($this->charset ?: 'UTF-8');
    }

    /**
     * @return $this
     */
    public function sendContent() {
        $this->content = json_encode($this->data, $this->encodeOption);

        return parent::sendContent();
    }
}