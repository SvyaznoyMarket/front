<?php
namespace EnterModel\Cart\Split\Order;
use EnterModel as Model;

class Delivery {
    /** @var string|null */
    public $methodToken;
    /** @var string|null */
    public $modeId;
    /** @var string|null */
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

    public function __construct($data = []) {
        if (isset($data['delivery_method_token'])) {
            $this->methodToken = (string)$data['delivery_method_token'];
        }

        if (isset($data['mode_id'])) {
            $this->modeId = (string)$data['mode_id'];
        }

        if (isset($data['date'])) {
            $this->date = (string)$data['date'];
        }

        if (isset($data['price'])) {
            $this->price = (string)$data['price'];
        }

        if (isset($data['interval'])) {
            $this->interval = new Model\Cart\Split\Interval($data['interval']);
        }

        if (isset($data['point'])) {
            $this->point = new Delivery\Point($data['point']);
        }

        if (isset($data['use_user_address'])) {
            $this->useUserAddress = (bool)$data['use_user_address'];
        }

        if (isset($data['type_id'])) {
            $this->typeId = (string)$data['type_id'];
        }

        if (isset($data['type_ui'])) {
            $this->typeUi = (string)$data['type_ui'];
        }
    }
}
