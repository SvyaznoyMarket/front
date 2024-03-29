<?php
namespace EnterModel\Cart\Split\Order;

class Discount {
    /** @var string */
    public $ui = '';
    /** @var string */
    public $name = '';
    /** @var string */
    public $discount = '';
    /** @var string */
    public $unit = 'rub';
    /** @var string */
    public $type = '';
    /** @var string */
    public $number = '';
    /** @var \EnterModel\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        if (isset($data['uid'])) $this->ui = (string)$data['uid'];
        if (isset($data['name'])) $this->name = (string)$data['name'];
        if (isset($data['discount'])) $this->discount = (string)$data['discount'];
        if (isset($data['type'])) $this->type = (string)$data['type'];
        if (isset($data['number'])) $this->number = (string)$data['number'];

        $this->media = new \EnterModel\MediaList();
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'ui'       => $this->ui,
            'name'     => $this->name,
            'discount' => $this->discount,
            'type'     => $this->type,
            'number'   => $this->number,
        ];
    }
}
