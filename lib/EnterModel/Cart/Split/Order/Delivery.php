<?php
namespace EnterModel\Cart\Split\Order;
use EnterModel as Model;

class Delivery {
    /** @var string|null */
    public $methodToken;
    /** @var string|null */
    public $modeId;
    /** @var int|null */
    public $date;
    /** @var string|null */
    public $price;
    /** @var Model\Cart\Split\Interval|null */
    public $interval;
    /** @var Delivery\Point|null */
    public $point;
    /** @var bool|null */
    public $useUserAddress;
    /** @var string|null */
    public $typeId;
    /** @var string|null */
    public $typeUi;
    /** @var string */
    public $boxUi;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->methodToken = $data['delivery_method_token'] ? (string)$data['delivery_method_token'] : null;
        $this->modeId = $data['mode_id'] ? (string)$data['mode_id'] : null;
        $this->date = (int)$data['date'];
        $this->price = $data['price'] ? (string)$data['price'] : null;
        $this->interval = $data['interval'] ? new Model\Cart\Split\Interval($data['interval']) : null;
        $this->point = $data['point'] ? new Delivery\Point($data['point']) : null;
        $this->useUserAddress = (bool)$data['use_user_address'];
        $this->typeId = !empty($data['type_id']) ? (string)$data['type_id'] : null; // FIXME
        $this->typeUi = !empty($data['type_ui']) ? (string)$data['type_ui'] : null; // FIXME
        if (isset($data['box_ui'])) $this->boxUi = $data['box_ui'];
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'delivery_method_token' => $this->methodToken,
            'mode_id'               => $this->modeId,
            'date'                  => $this->date,
            'price'                 => $this->price,
            'interval'              => $this->interval ? $this->interval->dump() : null,
            'point'                 => $this->point ? $this->point->dump() : null,
            'use_user_address'      => $this->useUserAddress,
            'type_id'               => $this->typeId,
            'type_ui'               => $this->typeUi,
            'box_ui'                => $this->boxUi,
        ];
    }
}
