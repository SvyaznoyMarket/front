<?php

namespace EnterQuery;

class CoreQueryException extends \Exception implements \JsonSerializable {
    /** @var array */
    private $detail = [];

    /**
     * @param array $detail
     */
    public function setDetail(array $detail) {
        $this->detail = $detail;
    }

    /**
     * @return array
     */
    public function getDetail() {
        return $this->detail;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return [
            'code'    => $this->getCode(),
            'message' => $this->getMessage(),
            'detail'  => $this->getDetail(),
            'file'    => $this->getFile(),
            'line'    => $this->getLine(),
        ];
    }
}