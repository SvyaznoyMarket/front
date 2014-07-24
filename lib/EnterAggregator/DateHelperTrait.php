<?php

namespace EnterAggregator;

use Enter\Helper;

trait DateHelperTrait {
    /**
     * @return Helper\Date
     */
    protected function getDateHelper() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getDateHelper();
    }
}