<?php

namespace EnterMobile\Routing\Product\Label;

use EnterMobile\Routing\Route;
use EnterMobile\ConfigTrait;
use EnterMobile\Config;

class Get extends Route {
    use ConfigTrait;

    /** @var Config */
    protected $config;

    /**
     * @param string $labelId
     * @param string $labelSource
     * @param string $labelSize
     */
    public function __construct($labelId, $labelSource, $labelSize) {
        $this->config = $this->getConfig();

        $this->parameters = [
            'labelId'     => $labelId,
            'labelSource' => $labelSource,
            'labelSize'   => $labelSize,
        ];
    }

    /**
     * @return string
     */
    public function __toString() {
        return
            $this->getHost((int)$this->parameters['labelId'])
            . (array_key_exists($this->parameters['labelSize'], $this->config->productLabel->urlPaths) ? $this->config->productLabel->urlPaths[$this->parameters['labelSize']] : reset($this->config->productLabel->urlPaths))
            . $this->parameters['labelSource']
        ;
    }

    /**
     * @param int $labelId
     * @return string
     */
    protected function getHost($labelId) {
        $hosts = $this->config->mediaHosts;
        $index = !empty($labelId) ? ($labelId % count($hosts)) : rand(0, count($hosts) - 1);

        return isset($hosts[$index]) ? $hosts[$index] : '';
    }
}