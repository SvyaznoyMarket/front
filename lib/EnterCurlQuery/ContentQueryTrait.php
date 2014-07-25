<?php

namespace EnterCurlQuery;

use EnterCurlQuery\Url;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use Enter\Util;

/**
 * @property Url $url
 * @property array $data
 * @property int $timeout
 * @property string $auth
 * @property \Exception|null $error
 * @property string $response
 */
trait ContentQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->contentService;

        $this->url->prefix = $config->url;
        $this->url->query = [
            'json' => '1',
        ];
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
                $response = array_merge(['code' => 0, 'message' => null], $response['error']);

                throw new \Exception($response['message'], $response['code']);
            }
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}