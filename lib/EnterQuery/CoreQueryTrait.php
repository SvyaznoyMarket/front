<?php

namespace EnterQuery;

use EnterQuery\Url;
use EnterAggregator\ConfigTrait;
use Enter\Util;

/**
 * @property Url $url
 * @property int $timeout
 * @property \Exception|null $error
 * @property string $response
 */
trait CoreQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->coreService;

        $this->url->prefix = $config->url;
        if (!isset($this->url->query['client_id'])) {
            $this->url->query['client_id'] = $config->clientId;
        }

        if ($config->debug) {
            $this->url->query['log4php'] = 'debug';
        }
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
            if (array_key_exists('error', $response)) {
                $response = array_merge(['code' => 0, 'message' => null, 'detail' => []], $response['error']);

                $e = new CoreQueryException($response['message'], $response['code']);
                $e->setDetail((array)$response['detail']);

                throw $e;
            } else if (array_key_exists('result', $response)) {
                $response = $response['result'];
            }
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}