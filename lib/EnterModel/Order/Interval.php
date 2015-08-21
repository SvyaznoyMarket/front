<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Interval {
    /** @var string */
    public $from;
    /** @var string */
    public $to;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('time_begin', $data)) $this->from = (string)$data['time_begin'];
        if (array_key_exists('time_end', $data)) $this->to = (string)$data['time_end'];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        if (array_key_exists('from', $data)) $this->from = (string)$data['from'];
        if (array_key_exists('to', $data)) $this->to = (string)$data['to'];
    }
}