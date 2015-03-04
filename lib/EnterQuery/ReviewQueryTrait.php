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
 * @property string $auth
 * @property \Exception|null $error
 * @property string $response
 */
trait ReviewQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->reviewService;

        $this->dataEncoder = 'json_encode';
        $this->url->prefix = $config->url;
        $this->timeout = $config->timeout;
        if ($config->user && $config->password) {
            $this->auth = $config->user . ':' . $config->password;
        }
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
            if (isset($response['error']['message'])) {
                $response = array_merge(['code' => 0, 'message' => null], $response['error']);

                throw new \Exception($response['message'], $response['code']);
            }
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}