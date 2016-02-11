<?php
namespace EnterModel;

class Prepayment {
    /** @var string */
    public $sum = '';
    /** @var string */
    public $message = 'Требуется предоплата.';
    /** @var string */
    public $contentId = 'how_pay';

    /**
     * @param string|int|float|null $prepaymentSum
     */
    public function __construct($prepaymentSum) {
        $this->sum = !empty($prepaymentSum) ? (string)$prepaymentSum : '';
    }
}
