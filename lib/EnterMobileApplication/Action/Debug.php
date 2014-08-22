<?php

namespace EnterMobileApplication\Action;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\RequestIdTrait;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\DebugContainerTrait;

class Debug {
    use RequestIdTrait, ConfigTrait, LoggerTrait, SessionTrait, TemplateHelperTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @param Http\Response $response
     * @param \Exception $error
     * @param $startAt
     * @param $endAt
     */
    public function execute(Http\Request $request = null, Http\Response $response = null, \Exception $error = null, $startAt, $endAt) {
        $config = $this->getConfig();
        $logger = $this->getLogger();

        if (!$config->debugLevel) {
            return;
        }

        $page = new \StdClass();

        // request id
        $page->requestId = $this->getRequestId();

        // error
        if ($error) {
            $page->error = [
                'message' => $error->getMessage(),
                'type'    => $error->getCode(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
            ];
        }
        else if ($lastError = error_get_last()) {
            $page->error = $lastError;
        } else if (isset($this->getDebugContainer()->error) && ($this->getDebugContainer()->error instanceof \Exception)) {
            $page->error = [
                'message' => $this->getDebugContainer()->error->getMessage(),
                'type'    => $this->getDebugContainer()->error->getCode(),
                'file'    => $this->getDebugContainer()->error->getFile(),
                'line'    => $this->getDebugContainer()->error->getLine(),
            ];
        }

        // curl query
        $i = 0;
        foreach ($logger as $message) {
            if (isset($message['tag'][0]) && in_array('curl', $message['tag'])) {
                /** @var Query|null $curlQuery */
                $curlQuery = (isset($message['query']) && $message['query'] instanceof Query) ? $message['query'] : null;
                if (!$curlQuery) continue;

                $query = new \StdClass();

                if ($config->curl->logResponse) {
                    try {
                        // TODO: в response должны записываться данные из $curlQuery->response
                        $query->response = $curlQuery->getResult();
                    } catch (\Exception $e) {}
                }

                if ($queryError = $curlQuery->getError()) {
                    $query->error = [
                        'code'    => $queryError->getCode(),
                        'message' => $queryError->getMessage(),
                        'file'    => $queryError->getFile(),
                        'line'    => $queryError->getLine(),
                    ];
                }

                $info = $curlQuery->getInfo();

                $query->code = $info['http_code'];
                $query->url = urldecode((string)$curlQuery->getUrl());
                if ((bool)$curlQuery->getData()) {
                    $query->data = $curlQuery->getData();
                }
                $query->call = $curlQuery->getCall();
                $query->time = round(($curlQuery->getEndAt() - $curlQuery->getStartAt()), 3) * 1000;

                $page->queries[] = $query;

                $i++;
            }
        }

        // git
        try {
            $page->git = new \StdClass();
            $page->git->branch = trim(shell_exec(sprintf('cd %s && git rev-parse --abbrev-ref HEAD', realpath($config->dir))));
            $page->git->tag = trim(shell_exec(sprintf('cd %s && git describe --always --tag', realpath($config->dir))));
        } catch (\Exception $e) {
            $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['debug']]);
        }

        // session
        if (isset($GLOBALS['enter.http.session'])) {
            $page->session = $this->getSession()->all();
        }

        // config
        //$page->config = $config;

        // данные из контейнера отладки
        foreach (get_object_vars($this->getDebugContainer()) as $key => $value) {
            if (isset($page->{$key})) {
                $logger->push(['type' => 'warn', 'error' => sprintf('Свойство %s уже существует', $key), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['debug']]);
                continue;
            }

            $page->{$key} = $value;
        }

        if ($response instanceof Http\JsonResponse) {
            $response->data['debug'] = $page;
        }
    }
}