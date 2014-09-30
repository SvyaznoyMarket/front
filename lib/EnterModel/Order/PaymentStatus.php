<?php

namespace EnterModel\Order;

use EnterModel as Model;

class PaymentStatus {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string[] */
    private $namesById = [
        1 => 'Не оплачен',
        2 => 'Оплачен',
        3 => 'Частично оплачен',
        4 => 'Начало оплаты',
        5 => 'Отмена оплаты'
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->id = @$data['id'] ? (string)$data['id'] : null;
        $this->name = @$this->namesById[$this->id] ? $this->namesById[$this->id] : null;
    }
}