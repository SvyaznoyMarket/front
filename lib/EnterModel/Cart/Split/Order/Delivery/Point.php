<?php
namespace EnterModel\Cart\Split\Order\Delivery;

class Point {
    /** @var string|null */
    public $groupToken;
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->groupToken = $data['token'] ? (string)$data['token'] : null;
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'token' => $this->groupToken,
            'id'    => $this->id,
            'ui'    => $this->ui,
        ];
    }
}
