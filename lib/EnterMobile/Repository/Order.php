<?php

namespace EnterMobile\Repository;

class Order {
    /**
     * @return array
     */
    public function getPaymentImagesByPaymentMethodId() {
        return [
            '5'  => 'i-bank-cart.png',
            '17' => 'i-bank-cart.png',
            '20' => 'i-bank-cart.png',
            '22' => 'i-robokassa.png',
            '16' => 'i-ya-wallet.png',
            '21' => 'i-ya-wallet.png',
            '11' => 'i-webmoney.png',
            '19' => 'i-webmoney.png',
            '12' => 'i-qiwi.png',
            '18' => 'i-qiwi.png',
            '8'  => 'i-psb.png',
        ];
    }
}