<?php
namespace EnterModel\Cart\Split;

class Interval {
    /** @var string|null */
    public $from;
    /** @var string|null */
    public $to;

    public function __construct($data = []) {
        if (isset($data['from'])) {
            $this->from = (string)$data['from'];
        }

        if (isset($data['to'])) {
            $this->to = (string)$data['to'];
        }
    }
}
