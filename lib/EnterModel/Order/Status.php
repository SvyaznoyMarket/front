<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Status {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string[] */
    private $namesById = [
        1   => 'Ваш заказ принят в обработку',
        2   => 'Подтвержден',
        3   => 'Заказ размещен у поставщика',
        4   => 'Заказ передан в доставку',
        5   => 'Получен',
        6   => 'Готов',
        10  => 'Новый',
        20  => 'Заказ готов к передаче',
        100 => 'Отменен',
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->id = @$data['id'] ? (string)$data['id'] : null;
        $this->name = @$this->namesById[$this->id] ? $this->namesById[$this->id] : null;
    }
}