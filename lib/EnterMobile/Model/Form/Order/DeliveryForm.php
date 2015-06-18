<?php

namespace EnterMobile\Model\Form\Order;

class DeliveryForm {
    /** @var string */
    public $url;

    /**
     * Json-строка с ошибками формы
     * @var string
     */
    public $errorDataValue;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
    }
}