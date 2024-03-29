<?php

namespace Enter\Curl;

use Enter\Logging\Logger;

/**
 * Class Client
 * @package Curl
 * @author Georgiy Lazukin <georgiy.lazukin@gmail.com>
 * @author Sergey Sapego <sapegosv@gmail.com>
 */
class Client {
    /** @var Logger */
    private $logger;
    /** @var resource */
    private $multiConnection;
    /** @var resource[] */
    private $connections = [];
    /** @var Query[] */
    private $queries = [];
    /** @var bool */
    private $stillExecuting = false;
    /** @var Config */
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger = null) {
        $this->logger = $logger;
    }

    public function __clone() {
        $this->multiConnection = null;
        $this->connections = [];
        $this->queries = [];
        $this->stillExecuting = false;
    }

    /**
     * @param Query $query
     * @return $this
     * @throws \Exception
     */
    public function query(Query $query) {
        $query->incCall();

        $connection = $this->create($query);

        $query->setStartAt(microtime(true));

        $response = curl_exec($connection);
        try {
            $info = curl_getinfo($connection);
            $query->setInfo($info);

            if (curl_errno($connection) > 0) {
                throw new \RuntimeException(curl_error($connection), curl_errno($connection));
            }

            $headers = [];
            $this->parseResponse($connection, $response, $headers);
            $query->setResponseHeaders($headers);

            if (is_resource($connection)) {
                curl_close($connection);
            }

            $query->setEndAt(microtime(true));

            $this->handleHttpError($query, $info, $response);

            $query->callback($response);

            if ($this->logger) $this->logger->push(['sender' => __FILE__ . ' ' .  __LINE__, 'query' => $query, 'tag' => ['curl']]);

            return $this;
        } catch (\Exception $e) {
            $query->setError($e);
            if (!$query->getEndAt()) {
                $query->setEndAt(microtime(true));
            }

            if ($this->logger) $this->logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'query' => $query, 'tag' => ['curl']]);

            if (is_resource($connection)) {
                curl_close($connection);
            }

            throw $e;
        }
    }

    /**
     * @param float|null $retryTimeout
     * @param int|null $retryCount
     * @return $this
     * @throws \Exception
     */
    public function execute($retryTimeout = null, $retryCount = null) {
        if (!$this->multiConnection) {
            if ($this->logger) $this->logger->push(['type' => 'warn', 'sender' => __FILE__ . ' ' .  __LINE__, 'message' => 'Нет запросов для выполнения', 'tag' => ['curl']]);

            return $this;
        }

        if (null === $retryTimeout) {
            $retryTimeout = $this->config->retryTimeout;
        }
        if (null === $retryCount) {
            $retryCount = $this->config->retryCount;
        }

        try {
            $absoluteTimeout = microtime(true);

            foreach ($this->queries as $query) {
                if ($query->getStartAt()) continue;

                $query->setStartAt($absoluteTimeout);
            }

            do {
                if ($absoluteTimeout <= microtime(true)) {
                    $absoluteTimeout += $retryTimeout;
                }

                do {
                    $code = curl_multi_exec($this->multiConnection, $stillExecuting);
                    $this->stillExecuting = $stillExecuting;
                } while ($code == CURLM_CALL_MULTI_PERFORM);

                // if one or more descriptors is ready, read content and run callbacks
                while ($done = curl_multi_info_read($this->multiConnection)) {
                    $connection = $done['handle'];
                    $queryId = (string)$connection;

                    foreach ($this->queries[$queryId]->getConnections() as $resource) {
                        if (is_resource($resource) && ($resource !== $connection)) {
                            curl_multi_remove_handle($this->multiConnection, $resource);
                            curl_close($resource);
                        }
                    }

                    try {
                        $info = curl_getinfo($connection);
                        $this->queries[$queryId]->setInfo($info);

                        if (curl_errno($connection) > 0) {
                            throw new \RuntimeException(curl_error($connection), curl_errno($connection));
                        }

                        $response = curl_multi_getcontent($connection);

                        $headers = [];
                        $this->parseResponse($connection, $response, $headers);
                        $this->queries[$queryId]->setResponseHeaders($headers);

                        if (is_resource($connection)) {
                            curl_multi_remove_handle($this->multiConnection, $connection);
                            curl_close($connection);
                        }

                        $this->queries[$queryId]->setEndAt(microtime(true));

                        $this->handleHttpError($this->queries[$queryId], $info, $response);

                        // TODO: отложенный запуск обработчиков
                        $this->queries[$queryId]->callback($response);

                        if ($this->logger) $this->logger->push(['sender' => __FILE__ . ' ' .  __LINE__, 'query' => $this->queries[$queryId], 'tag' => ['curl']]);

                        unset($this->queries[$queryId]);
                    } catch (\Exception $e) {
                        $this->queries[$queryId]->setError($e);
                        if (!$this->queries[$queryId]->getEndAt()) {
                            $this->queries[$queryId]->setEndAt(microtime(true));
                        }

                        if ($this->logger) $this->logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'query' => $this->queries[$queryId], 'tag' => ['curl']]);

                        if (is_resource($connection)) {
                            curl_multi_remove_handle($this->multiConnection, $connection);
                            curl_close($connection);
                        }
                    }
                }

                if ($stillExecuting) {
                    $timeout = $absoluteTimeout - microtime(true);
                    if (0 >= $timeout) {
                        $timeout += $retryTimeout;
                        $absoluteTimeout += $retryTimeout;
                    }
                    $tryAvailable = false;
                    foreach ($this->queries as $query) {
                        if (count($query->getConnections()) < ($query->getRetry() ?: $retryCount)) {
                            $tryAvailable = true;
                            break;
                        }
                    }
                    if ($tryAvailable && null !== $retryTimeout) {
                        $ready = curl_multi_select($this->multiConnection, $timeout);
                    } else {
                        $ready = curl_multi_select($this->multiConnection, $timeout);
                    }

                    if (0 === $ready) {
                        foreach ($this->queries as $query) {
                            if (count($query->getConnections()) >= ($query->getRetry() ?: $retryCount)) {
                                /*
                                $query->setEndAt(microtime(true));
                                $query->setError(new \Exception(sprintf('Запрос %s отменен по таумауту', mb_substr($query->getUrl(), 0, 256))));

                                if ($this->logger) $this->logger->push(['type' => 'error', 'error' => $query->getError(), 'sender' => __FILE__ . ' ' .  __LINE__, 'query' => $query, 'tag' => ['curl']]);

                                foreach ($query->getConnections() as $connection) {
                                    if (is_resource($connection)) {
                                        curl_multi_remove_handle($this->multiConnection, $connection);
                                        curl_close($connection);
                                    }

                                    unset($this->queries[$query->getId()]);
                                }
                                */

                                continue;
                            }
                            $this->prepare($query);
                        }
                    }
                }
            } while ($this->stillExecuting);
        } catch (\Exception $e) {
            $this->clear();

            if ($this->logger) $this->logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'error' => ['code' => $e->getCode(), 'message' => $e->getMessage()], 'tag' => ['curl']]);

            throw $e;
        }

        $this->clear();

        return $this;
    }

    public function clear() {
        foreach ($this->connections as $resource) {
            if (is_resource($resource)) {
                curl_multi_remove_handle($this->multiConnection, $resource);
                curl_close($resource);
            }
        }
        curl_multi_close($this->multiConnection);
        $this->multiConnection = null;
        $this->connections = [];
        $this->queries = [];

        return $this;
    }

    /**
     * @param Query $query
     * @return $this
     * @throws \Exception
     */
    public function prepare(Query $query) {
        $query->incCall();

        if (!$this->multiConnection) {
            $this->multiConnection = curl_multi_init();
        }

        $resource = $this->create($query);
        if (0 !== curl_multi_add_handle($this->multiConnection, $resource)) {
            $message = curl_error($resource);
            if ($this->logger) $this->logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'message' => $message, 'tag' => ['curl']]);

            throw new \Exception($message);
        };
        $this->connections[] = $resource;

        $this->stillExecuting = true;

        $this->queries[$query->getId()] = $query;

        return $this;
    }

    /**
     * @param Query $query
     * @return resource
     */
    private function create(Query $query) {

        $connection = curl_init();
        curl_setopt($connection, CURLOPT_HEADER, true);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($connection, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($connection, CURLOPT_URL, $query->getUrl());

        $headers = [];
        if ((bool)$query->getHeaders()) {
            $headers = $query->getHeaders();
        } else if ((bool)$this->config->httpheader) {
            $headers = $this->config->httpheader;
        }

        if ($this->config->encoding) {
            curl_setopt($connection, CURLOPT_ENCODING, $this->config->encoding);
        }

        if ($query->getTimeout()) {
            curl_setopt($connection, CURLOPT_NOSIGNAL, true);
            curl_setopt($connection, CURLOPT_TIMEOUT_MS, $query->getTimeout() * 1000);
        } else {
            if ($this->logger) $this->logger->push(['type' => 'error', 'message' => 'Не установлен timeout', 'sender' => __FILE__ . ' ' .  __LINE__, 'query' => $query, 'tag' => ['curl']]);
        }

        if ($query->getAuth()) {
            curl_setopt($connection, CURLOPT_USERPWD, $query->getAuth());
        }

        if ((bool)$query->getData()) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($connection, CURLOPT_POST, true);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $query->getDataEncoder() ? call_user_func($query->getDataEncoder(), $query->getData()) : $query->getData());
        }

        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        if ($this->config->referer) {
            curl_setopt($connection, CURLOPT_REFERER, $this->config->referer);
        }

        if ($this->config->debug) {
            curl_setopt($connection, CURLINFO_HEADER_OUT, true);
        }

        $query->setId((string)$connection);
        $query->addConnection($connection);

        return $connection;
    }

    /**
     * @param resource $connection
     * @param string $response
     * @param array|null $headers
     */
    private function parseResponse($connection, &$response, &$headers = null) {
        $size = curl_getinfo($connection, CURLINFO_HEADER_SIZE);

        if (is_array($headers)) {
            foreach (explode("\r\n", mb_substr($response, 0, $size)) as $line) {
                if ($pos = strpos($line, ':')) {
                    $key = substr($line, 0, $pos);
                    $value = trim(substr($line, $pos + 1));
                    $headers[$key] = $value;
                } else {
                    $headers[] = $line;
                }
            }
        }

        $response = mb_substr($response, $size);
    }

    /**
     * @param Query $query
     * @param array $info
     * @param string $response
     * @throws \Exception
     */
    private function handleHttpError(Query $query, $info, $response) {
        if ($info['http_code'] >= 300) {
            $query->setResponse(preg_replace('/\r?\n/', ' ', mb_substr($response, 0, 1024)));
            throw new \Exception('Неверный статус ответа', $info['http_code']);
        }

        if (null === $response) {
            throw new \Exception(sprintf('Пустой ответ от %s', $query->getUrl()));
        }
    }
}