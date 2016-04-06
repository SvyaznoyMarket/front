<?php
namespace EnterModel\Cart\Split\Order;

class Certificate {
    /** @var string */
    public $code = '';
    /** @var string */
    public $pin = '';
    /** @var string */
    public $par = '';

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
