<?php
namespace EnterModel\Cart\Split\Order;

class Certificate {
    /** @var string|null */
    public $code;
    /** @var string|null */
    public $pin;
    /** @var string|null */
    public $par;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        if (isset($data['code'])) {
            $this->code = (string)$data['code'];
        }

        if (isset($data['pin'])) {
            $this->pin = (string)$data['pin'];
        }

        if (isset($data['par'])) {
            $this->par = (string)$data['par'];
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'code' => $this->code,
            'pin'  => $this->pin,
            'par'  => $this->par,
        ];
    }
}