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
}