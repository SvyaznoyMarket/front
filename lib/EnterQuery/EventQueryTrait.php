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
trait EventQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->eventService;

        $this->dataEncoder = 'json_encode';
        $this->url->prefix = $config->url;
        if ($this->data) {
            $this->data['client_id'] = $config->clientId;
        } else {
            $this->url->query['client_id'] = $config->clientId;
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