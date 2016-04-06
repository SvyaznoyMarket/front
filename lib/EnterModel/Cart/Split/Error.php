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
            $detailItem = (array)$data['details'] + ['block_name' => null];

            switch ($this->code) {
                case 708:
                    $this->detail = [
                        'product'              => [
                            'id'   => isset($detailItem['product_id']) ? $detailItem['product_id'] : null,
                            'ui'   => isset($detailItem['product_ui']) ? $detailItem['product_ui'] : null,
                            'name' => isset($detailItem['product_name']) ? $detailItem['product_name'] : null,
                        ],
                        'maxAvailableQuantity' => isset($detailItem['max_available_quantity']) ? (int)$detailItem['max_available_quantity'] : 0,
                        'requestedQuantity'    => isset($detailItem['requested_amount']) ? (int)$detailItem['requested_amount'] : 0,
                        'blockName'            => isset($detailItem['block_name']) ?: null,
                    ];
                    $this->message = $this->detail['maxAvailableQuantity'] ? sprintf('Доступно только %d шт', $this->detail['maxAvailableQuantity']) : 'Товар закончился';
                    break;
                case 404:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['coupon_number'] ? (string)$detailItem['coupon_number'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = 'Такой купон не существует';
                    break;
                case 1022:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['coupon_number'] ? (string)$detailItem['coupon_number'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = 'Срок действия купона истек';
                    break;
                case 1021:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['coupon_number'] ? (string)$detailItem['coupon_number'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = 'Срок действия купона еще не наступил';
                    break;
                case 1000:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['coupon_number'] ? (string)$detailItem['coupon_number'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = 'К купону не привязана акция';
                    break;
                case 1001:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['coupon_number'] ? (string)$detailItem['coupon_number'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];
                    $this->message = 'Купон не может быть применен к этому заказу';
                    break;
                case 738:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['certificate_code'] ? (string)$detailItem['certificate_code'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];

                    $this->message = 'Сертификат не активирован';
                    break;
                case 739:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['certificate_code'] ? (string)$detailItem['certificate_code'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];

                    $this->message = 'Сертификат уже применён в другом заказе';
                    break;
                case 740:
                    $this->detail = [
                        'discount'  => [
                            'number' => @$detailItem['certificate_code'] ? (string)$detailItem['certificate_code'] : null,
                        ],
                        'blockName' => @$detailItem['block_name'] ?: null,
                    ];

                    if (preg_match('/\[(\d+)\]/', (string)$data['message'], $matches)) {
                        switch ($matches[1]) {
                            case 742:
                                $this->message = 'Неверный пин-код сертификата';
                                break;
                            case 743:
                                $this->message = 'Сертификат не найден';
                                break;
                        }
                    }
                    break;
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
