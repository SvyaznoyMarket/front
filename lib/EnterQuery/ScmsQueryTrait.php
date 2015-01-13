<?php

namespace EnterQuery;

use EnterQuery\Url;
use EnterAggregator\ConfigTrait;
use Enter\Util;

/**
 * @property Url $url
 * @property array $data
 * @property callable|null $dataEncoder
 * @property int $timeout
 * @property \Exception|null $error
 * @property string $response
 */
trait ScmsQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->scmsService;

        $this->dataEncoder = 'json_encode';
        $this->url->prefix = $config->url;
        $this->timeout = $config->timeout;
    }

    /**
     * @param $response
     * @return array
     */
    protected function parse($response) {
        if ($this->getConfig()->curl->logResponse) {
            $this->response = $response;
        }

        try {
            $response = Util\Json::toArray($response);
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}