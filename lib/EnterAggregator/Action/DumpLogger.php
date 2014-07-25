<?php

namespace EnterAggregator\Action;

use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;

class DumpLogger {
    use ConfigTrait, LoggerTrait;

    public function execute() {
        try {
            $this->getLogger()->dump();
        } catch (\Exception $e) {
            trigger_error($e, E_USER_ERROR);
        }
    }
}