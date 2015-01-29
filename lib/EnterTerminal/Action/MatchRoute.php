<?php

namespace EnterTerminal\Action;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterTerminal\Controller;

class MatchRoute {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @return callable
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();

        $callable = null;

        try {
            $pathInfo = $request->getPathInfo();
            if ($config->version) {
                $pathInfo = preg_replace('/^\/' . preg_quote($config->version) . '/', '', $pathInfo);
            }

            $controllerClass = '\\EnterTerminal\\Controller\\' . implode('\\', array_map('ucfirst', explode('/', trim($pathInfo, '/')))); // TODO: перенести в настройки
            $this->getLogger()->push(['controller' => $controllerClass, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['routing']]);
            $callable = [new $controllerClass, 'execute'];
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['routing']]);

            //$callable = [new Controller\Error\NotFound(), 'execute'];
            throw $e;
        }

        return $callable;
    }
}