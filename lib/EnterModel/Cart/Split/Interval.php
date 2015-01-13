<?php
namespace EnterModel\Cart\Split;

class Interval {
    /** @var string|null */
    public $from;
    /** @var string|null */
    public $to;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->from = $data['from'] ? (string)$data['from'] : null;
        $this->to = $data['to'] ? (string)$data['to'] : null;
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
