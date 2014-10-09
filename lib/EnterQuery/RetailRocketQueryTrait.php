<?php

namespace EnterQuery;

use EnterQuery\RetailRocketUrl;
use EnterAggregator\ConfigTrait;
use Enter\Util;

/**
 * @property RetailRocketUrl $url
 * @property array $data
 * @property callable|null $dataEncoder
 * @property int $timeout
 * @property string $auth
 * @property \Exception|null $error
 * @property string $response
 */
trait RetailRocketQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->retailRocketService;

        $this->dataEncoder = 'json_encode';
        $this->url->prefix = $config->url;
        $this->url->account = $config->account;
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
            // TODO: обработка ошибок
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}