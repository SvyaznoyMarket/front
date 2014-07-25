<?php

namespace EnterAggregator;

use Enter\Curl;

trait CurlTrait {
    /**
     * @return Curl\Client
     */
    protected function getCurl() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getCurl();
    }
}