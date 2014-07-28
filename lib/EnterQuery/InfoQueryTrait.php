<?php

namespace EnterQuery;

use EnterQuery\Url;
use EnterSite\ConfigTrait;
use Enter\Util;

/**
 * @property Url $url
 * @property int $timeout
 * @property \Exception|null $error
 * @property string $response
 */
trait InfoQueryTrait {
    use ConfigTrait;

    protected function init() {
        $config = $this->getConfig()->infoService;

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
            if (array_key_exists('error', $response)) {
                $response = array_merge(['code' => 0, 'message' => null], $response['error']);

                throw new \Exception($response['message'], $response['code']);
            } else if (array_key_exists('result', $response)) {
                $response = $response['result'];
            }
        } catch (\Exception $e) {
            $this->error = $e;
        }

        return $response;
    }
}
