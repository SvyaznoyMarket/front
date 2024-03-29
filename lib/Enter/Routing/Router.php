<?php

namespace Enter\Routing;

class Router {
    /** @var Config */
    protected $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param Route $route
     * @param array $parameters
     * @return string
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function getUrlByRoute(Route $route, array $parameters = []) {
        $routeClass = str_replace($this->config->routeClassPrefix, '', get_class($route));

        if (!isset($this->config->routes[$routeClass])) {
            throw new \RuntimeException(sprintf('Неизвестный маршрут %s', $routeClass));
        }

        $routeItem = $this->config->routes[$routeClass];
        $parameters = array_merge($parameters, $route->parameters);
        $vars = [];

        if (isset($parameters['#'])) {
            $anchor = '#' . $parameters['#'];
            unset($parameters['#']);
        } else {
            $anchor = '';
        }

        // если в шаблоне нет переменных ...
        if (false === strpos($routeItem['pattern'], '{')) {
            $url = $routeItem['pattern'];
            // ... иначе
        } else {
            $patternReplaces = [];
            preg_match_all('#\{(\w+)\}#', $routeItem['pattern'], $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach ($matches as $match) {
                $varName = $match[1][0];
                if (!array_key_exists($varName, $parameters)) {
                    throw new \LogicException(sprintf('Не передан обязательный параметр %s для маршрута %s', $varName, $routeClass));
                }
                $patternReplaces['{' . $varName . '}'] = $parameters[$varName];
                $vars[$varName] = $parameters[$varName];
            }

            $url = strtr($routeItem['pattern'], $patternReplaces);
        }

        $extra = array_diff_key($parameters, $vars);
        if ((bool)$extra && $query = http_build_query($extra, '', '&')) {
            $url .= '?' . $query;
        }

        $url .= $anchor;

        // TODO: absolute

        return $url;
    }

    /**
     * @param $path
     * @param null $method
     * @param array $query
     * @param bool $needRedirect true, если маршрут был найден по алиасу и требуется сделать редирект на возвращённый маршрут
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \Exception
     * @return Route
     */
    public function getRouteByPath($path, $method = null, array $query = [], &$needRedirect = false) {
        $needRedirect = false;

        $path = rawurldecode($path);

        $route = null;

        foreach ($this->config->routes as $routeClass => $routeItem) {
            // Если не указан http-метод или http-метод совпадает с правилом маршрута ...
            if (!array_key_exists('method', $routeItem) || in_array($method, $routeItem['method'])) {
                $patterns = [];

                $patterns[] = [
                    'pattern' => $routeItem['pattern'],
                    'needRedirect' => false,
                ];

                if (isset($routeItem['aliases'])) {
                    foreach ($routeItem['aliases'] as $alias) {
                        if ($alias['pattern'] !== $routeItem['pattern']) {
                            $patterns[] = [
                                'pattern' => $alias['pattern'],
                                'needRedirect' => $alias['needRedirect'],
                            ];
                        }
                    }
                }

                foreach ($patterns as $pattern) {
                    $patternReplaces = [];
                    $varNames = [];
                    preg_match_all('#\{(\w+)\}#', $pattern['pattern'], $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        $varName = $match[1][0];
                        $patternReplaces['{' . $varName . '}'] = isset($routeItem['require'][$varName]) ? ('('.$routeItem['require'][$varName].')') : '([^\/]+)';
                        if (in_array($varName, $varNames)) {
                            throw new \LogicException(sprintf('Шаблон маршрута %s не может содержать более одного объявления переменной %s', $pattern['pattern'], $varName));
                        }
                        $varNames[] = $varName;
                    }

                    if (preg_match('#^' . strtr($pattern['pattern'], $patternReplaces) . '$#s', $path, $matches)) {
                        $vars = array_merge($query, array_combine($varNames, array_slice($matches, 1)));

                        $routeClass = $this->config->routeClassPrefix . $routeClass;
                        $reflectedClass = new \ReflectionClass($routeClass);

                        $arguments = [];
                        foreach ((new \ReflectionMethod($routeClass, '__construct'))->getParameters() as $reflectedParameter) {
                            if ($reflectedParameter->isDefaultValueAvailable()) {
                                $vars[$reflectedParameter->name] = $reflectedParameter->getDefaultValue();
                            }
                            if (!array_key_exists($reflectedParameter->name, $vars)) {
                                throw new \RuntimeException(sprintf('Маршруту %s необходим обязательный параметр %s', $routeClass, $reflectedParameter->name));
                            }
                            $arguments[] = $vars[$reflectedParameter->name];
                        }

                        $needRedirect = $pattern['needRedirect'];
                        return $reflectedClass->newInstanceArgs($arguments);
                    }
                }
            }
        }

        throw new \Exception(sprintf('Не найден маршрут для пути %s', $path));
    }
}