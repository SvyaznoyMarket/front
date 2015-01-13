<?php

namespace EnterMobile\Action;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;

class MatchRoute {
    use ConfigTrait, LoggerTrait, RouterTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return callable
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $router = $this->getRouter();
        $logger = $this->getLogger();

        $callable = null;

        try {
            $route = $router->getRouteByPath($request->getPathInfo(), $request->getMethod(), $request->query->all());

            if ($this->getConfig()->debugLevel) $this->getDebugContainer()->route = [
                'name'       => get_class($route),
                'action'     => $route->action,
                'parameters' => $route->parameters,
            ];

            $logger->push(['route' => [
                'name'       => get_class($route),
                'action'     => $route->action,
                'parameters' => $route->parameters,
            ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['route']]);

            if (isset($route->action[0])) {
                $controllerClass = '\\EnterMobile\\Controller\\' . $route->action[0]; // TODO: перенести в настройки
                $callable = [new $controllerClass, $route->action[1]];
            }
            if (!$callable || !is_callable($callable)) {
                throw new \Exception(sprintf('Маршруту %s не задан обработчик', get_class($route)));
            }

            // замена GET-параметров route-параметрами
            foreach ($route->parameters as $key => $value) {
                $request->query[$key] = $value;
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['routing']]);

            $callable = [new Controller\Error\NotFound(), 'execute'];
        }

        return $callable;
    }
}