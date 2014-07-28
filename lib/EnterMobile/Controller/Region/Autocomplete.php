<?php

namespace EnterMobile\Controller\Region;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterMobile\Routing;

class Autocomplete {
    use ConfigTrait, LoggerTrait, RouterTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $router = $this->getRouter();

        $result = [];

        $keyword = trim((string)$request->query['q']);

        $regionListQuery = new Query\Region\GetListByKeyword($keyword);
        $curl->prepare($regionListQuery)->execute();

        $i = 0;
        foreach ($regionListQuery->getResult() as $regionItem) {
            if ($i >= 20) break;

            $result[] = [
                'name'  => $regionItem['name'] . ((!empty($regionItem['region']['name']) && ($regionItem['name'] != $regionItem['region']['name'])) ? (" ({$regionItem['region']['name']})") : ''),
                'url'   => $router->getUrlByRoute(new Routing\Region\SetById($regionItem['id'])),
            ];

            $i++;
        }

        return new Http\JsonResponse([
            'result' => $result,
        ]);
    }
}