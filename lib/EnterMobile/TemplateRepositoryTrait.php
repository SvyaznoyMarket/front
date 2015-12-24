<?php

namespace EnterMobile;

use EnterMobile\Repository;

trait TemplateRepositoryTrait {
    /**
     * @return Repository\Template
     */
    protected function getTemplateRepository() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getTemplateRepository();
    }
}