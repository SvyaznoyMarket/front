<?php

namespace EnterMobile\Controller\Promo;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterModel as Model;

class Redirect {
    use ConfigTrait, LoggerTrait, CurlTrait, RouterTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $logger = $this->getLogger();
        $curl = $this->getCurl();
        $router = $this->getRouter();
        $promoRepository = new \EnterRepository\Promo();

        // ид баннера
        $promoId = $promoRepository->getIdByHttpRequest($request);

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // запрос баннеров
        $promoListQuery = new Query\Promo\GetList($config->applicationTags);
        $curl->prepare($promoListQuery);

        $curl->execute();

        // баннеры
        $promo = $promoRepository->getObjectByIdAndQuery($promoId, $promoListQuery);
        if (!$promo) {
            return (new \EnterAggregator\Controller\Redirect())->execute($router->getUrlByRoute(new Routing\Index()), 500);
        }
        //die(var_dump($promo));

        $url = '/';
        if (false !== strpos($promo->url, '/')) {
            $url = $promo->url;
        }

        return (new \EnterAggregator\Controller\Redirect())->execute($url, 302);
    }
}