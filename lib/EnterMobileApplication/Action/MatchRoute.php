<?php

namespace EnterMobileApplication\Action;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterMobileApplication\Controller;

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

            $controllerName = implode('\\', array_map('ucfirst', explode('/', trim($pathInfo, '/'))));
            if (!$controllerName) {
                $controllerName = 'Index';
            }

            $controllerClass = '\\EnterMobileApplication\\Controller\\' . $controllerName; // TODO: перенести в настройки

            $this->getLogger()->push(['controller' => $controllerClass, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['routing']]);
            $callable = [new $controllerClass, 'execute'];
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['routing']]);

            $callable = [new Controller\Error\NotFound(), 'execute'];
        }

        return $callable;
    }
}