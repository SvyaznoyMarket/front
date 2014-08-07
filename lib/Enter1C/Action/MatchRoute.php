<?php

namespace Enter1C\Action;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use Enter1C\Controller;

class MatchRoute {
    use LoggerTrait;

    /**
     * @param Http\Request $request
     * @return callable
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $callable = null;

        try {
            $controllerName = implode('\\', array_map('ucfirst', explode('/', trim($request->getPathInfo(), '/'))));
            if (!$controllerName) {
                $controllerName = 'Index';
            }

            $controllerClass = '\\Enter1C\\Controller\\' . $controllerName; // TODO: перенести в настройки

            $this->getLogger()->push(['controller' => $controllerClass, 'action' => __METHOD__, 'tag' => ['routing']]);
            $callable = [new $controllerClass, 'execute'];
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['routing']]);

            $callable = [new Controller\Error\NotFound(), 'execute'];
        }

        return $callable;
    }
}