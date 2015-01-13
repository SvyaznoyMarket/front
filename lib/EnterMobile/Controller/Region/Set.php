<?php

namespace EnterMobile\Controller\Region;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Controller;

class Set {
    use ConfigTrait, LoggerTrait, RouterTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $logger = $this->getLogger();
        $curl = $this->getCurl();

        $regionRepository = new \EnterRepository\Region();

        $regionId = $regionRepository->getIdByHttpRequestQuery($request);
        $keyword = trim((string)$request->query['q']);

        // response
        $response = (new \EnterAggregator\Controller\Redirect())->execute($request->server['HTTP_REFERER'] ?: $this->getRouter()->getUrlByRoute(new Routing\Index()), 302);

        if (!$regionId && (mb_strlen($keyword) >= 3)) {
            $regionListQuery = new Query\Region\GetListByKeyword($keyword);
            $curl->prepare($regionListQuery)->execute();

            $regionData = $regionListQuery->getResult();
            $regionId = isset($regionData[0]['id']) ? (string)$regionData[0]['id'] : null;
        }
        if (!$regionId) {
            $e = new \Exception('Не указан ид региона');
            $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['region']]);

            return $response;
        }

        // запрос региона
        $regionItemQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionItemQuery)->execute();

        // регион
        $region = $regionRepository->getObjectByQuery($regionItemQuery);
        if ($region) {
            $response->headers->setCookie(new Http\Cookie(
                $config->region->cookieName,
                $region->id,
                time() + $config->session->cookieLifetime,
                '/',
                $config->session->cookieDomain,
                false,
                false
            ));
        }

        return $response;
    }
}