<?php

namespace EnterMobile\Action;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Controller;

class CheckRedirect {
    use ConfigTrait, LoggerTrait, RouterTrait;

    /**
     * Временное решение для редиректа на основной домен
     * @param Http\Request $request
     * @return null
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $router = $this->getRouter();

        $route = null;
        try {
            $route = $router->getRouteByPath($request->getPathInfo(), $request->getMethod(), $request->query->all());
        } catch (\Exception $e) {}

        $hasRedirect = false
            //|| ($route instanceof Routing\Index)
            //|| ($route instanceof Routing\Search\Index)
            //|| ($route instanceof Routing\User\Login)
            //|| ($route instanceof Routing\ProductCatalog\GetBrandCategory)
            //|| ($route instanceof Routing\Content)
            || ($route instanceof Routing\User\Index)
            || ($route instanceof Routing\ShopCard\Get)
            || ($route instanceof Routing\Shop\Index)
            || ($route instanceof Routing\Order\Index)
        ;

        if (!$hasRedirect) {
            return null;
        }

        // FIXME
        //$url = str_replace('m.', '', $request->getSchemeAndHttpHost() . $request->getRequestUri());
        $url = strtr($request->getSchemeAndHttpHost(), [
            'm.'    => '',
            ':8080' => '', //FIXME: костыль для nginx-а
        ]) . $request->getRequestUri();

        return (new \EnterAggregator\Controller\Redirect())->execute($url, 302);
    }
}