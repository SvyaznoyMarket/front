<?php

namespace EnterAggregator;

use Enter\Helper;

trait PriceHelperTrait {
    /**
     * @return Helper\Price
     */
    protected function getPriceHelper() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getPriceHelper();
    }
}