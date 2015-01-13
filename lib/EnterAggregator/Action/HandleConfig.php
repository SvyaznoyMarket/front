<?php

namespace EnterAggregator\Action;

use EnterAggregator\ConfigTrait;

class HandleConfig {
    use ConfigTrait;

    /**
     * @param string $environment
     * @param int $debugLevel
     */
    public function execute($environment, $debugLevel) {
        $config = $this->getConfig();

        // environment & debug
        $config->environment = $environment;
        $config->debugLevel = $debugLevel;
        if ($config->debugLevel) {
            $config->logger->fileAppender->file = str_replace('.log', '-debug.log', $config->logger->fileAppender->file);
        }
        if ($config->debugLevel >= 2) {
            $config->curl->logResponse = true;
        }
        if ($config->debugLevel >= 3) {
            $config->coreService->debug = true;
        }

        // partner
        if (!$config->partner->enabled) {
            foreach (get_object_vars($config->partner->service) as $iConfig) {
                if (!isset($iConfig->enabled)) continue;

                $iConfig->enabled = false;
            }
        }
    }
}