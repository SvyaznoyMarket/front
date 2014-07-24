<?php

namespace EnterAggregator;

use Enter\Helper;

trait TranslateHelperTrait {
    /**
     * @return Helper\Translate
     */
    protected function getTranslateHelper() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getTranslateHelper();
    }
}