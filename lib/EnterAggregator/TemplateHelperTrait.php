<?php

namespace EnterAggregator;

use Enter\Helper;

trait TemplateHelperTrait {
    /**
     * @return Helper\Template
     */
    protected function getTemplateHelper() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getTemplateHelper();
    }
}