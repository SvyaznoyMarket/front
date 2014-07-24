<?php

namespace EnterAggregator;

use Enter\Helper;

trait UrlHelperTrait {
    /**
     * @return Helper\Url
     */
    protected function getUrlHelper() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getUrlHelper();
    }
}