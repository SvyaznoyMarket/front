<?php
namespace EnterModel\Cart\Split;

class Interval {
    /** @var string|null */
    public $from;
    /** @var string|null */
    public $to;

    public function __construct($data = []) {
        $this->from = $data['from'] ? (string)$data['from'] : null;
        $this->to = $data['to'] ? (string)$data['to'] : null;
    }
}
