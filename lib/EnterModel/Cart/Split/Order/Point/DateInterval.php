<?php

namespace EnterModel\Cart\Split\Order\Point;

class DateInterval {
    /** @var string */
    public $from;
    /** @var string */
    public $to;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->from = isset($data['from']) ? (string)$data['from'] : null;
        $this->to = isset($data['to']) ? (string)$data['to'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'from' => $this->from,
            'to'   => $this->to,
        ];
    }
}