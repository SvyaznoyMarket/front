<?php

namespace EnterCurlQuery;

use EnterCurlQuery\RetailRocketUrl;
use EnterSite\ConfigTrait;
use Enter\Util;

/**
 * @property RetailRocketUrl $url
 * @property array $data
 * @property int $timeout
 * @property string $auth
 * @property \Exception|null $error
 * @property string $response
 */
trait RetailRocketQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->retailRocketService;

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