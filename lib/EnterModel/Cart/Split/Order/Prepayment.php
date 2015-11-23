<?php
namespace EnterModel\Cart\Split\Order;

class Prepayment {
    /** @var string */
    public $sum = '';
    /** @var string */
    public $message = 'Требуется предоплата.';
    /** @var string */
    public $contentId = 'how_pay';

    /**
     * @param string|int|null $prepaymentSum
     */
    public function __construct($prepaymentSum) {
        $this->sum = (string)$prepaymentSum;
    }
}
