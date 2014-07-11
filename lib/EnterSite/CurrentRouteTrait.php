<?php

namespace EnterSite;

use EnterSite\Config;
use EnterSite\Routing;

trait CurrentRouteTrait {
    /**
     * @return Routing\Route
     */
    protected function getCurrentRoute() {
        return isset($GLOBALS[__METHOD__]) ? $GLOBALS[__METHOD__] : null;
    }
}