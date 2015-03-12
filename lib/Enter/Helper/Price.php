<?php

namespace Enter\Helper;

class Price {
    /**
     * @param float|int|string $price
     * @return string
     */
    public function format($price) {
        $price = str_replace(',', '.', $price);
        $price = preg_replace('/\s/', '', $price);
        $price = number_format($price, 2, '.', '');
        $price = explode('.', $price);

        /* Маленькие пробелы между разрядами целой части цены */
        if (strlen($price[0]) >= 5) {
            $price[0] = preg_replace('/(\d)(?=(\d\d\d)+([^\d]|$))/', '$1 ', $price[0]);
        }

        if (isset($price[1]) && $price[1] == 0) {
            unset($price[1]);
        }

        return implode('.', $price);
    }
}