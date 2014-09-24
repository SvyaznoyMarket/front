<?php
namespace EnterModel\Cart\Split;

class Error {
    /** @var int */
    public $code = 0;
    /** @var string|null */
    public $message;
    /** @var array */
    public $detail;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->code = (int)$data['code'];
        $this->message = (string)$data['message'];
        if (array_key_exists('details', $data)) {
            $detailItem = $data['details'];

            switch ($this->code) {
                case 708:
                    $this->detail = [
                        'productId'            => @$detailItem['product_id'] ?: null,
                        'productUi'            => @$detailItem['product_ui'] ?: null,
                        'maxAvailableQuantity' => @$detailItem['max_available_quantity'] ? (int)$detailItem['max_available_quantity'] : 0,
                        'requestedQuantity'    => @$detailItem['requested_amount'] ? (int)$detailItem['requested_amount'] : 0,
                        'blockName'            => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = $this->detail['maxAvailableQuantity'] ? sprintf('Доступно только %d шт', $this->detail['maxAvailableQuantity']) : 'Товар закончился';
            }
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'code'    => $this->code,
            'message' => $this->message,
        ];
    }
}
