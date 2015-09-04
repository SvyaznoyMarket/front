<?php

namespace EnterMobile\Controller\Shop;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterMobile\Routing;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Shops\Index as Page;

class GetList {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait,
        RouterTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $router = $this->getRouter();

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        $postData = $request->data->all();
        $requestFilter = [];

        $pointRepository = new \EnterRepository\Point();


        if (isset($postData['partners'])) {
            $requestFilter['partners'] = $postData['partners'];
        }

        if (isset($postData['phrase'])) {
            $coordinatesQuery = new \EnterQuery\Yandex\GetCoordinatesByPhrase($postData['phrase']);
            $curl->prepare($coordinatesQuery);
            $curl->execute();

            $requestFilter['coordinates'] = $this->findPointsWithinRange($coordinatesQuery->getResult());
        }

        $pq = new Query\Point\GetListFromScms($regionId, null, $requestFilter);
        $curl->prepare($pq);
        $curl->execute();

        $pqResult = $pq->getResult();

        $result = [
            'points' => array_values($pqResult['points']),
            'partners' => $pqResult['partners'],
            'mapCenter' => (isset($requestFilter['coordinates'])) ? $coordinatesQuery->getResult() : false
        ];


        $partners = [];


        $backLink = trim((string)($request->query['redirect_to'] ?: $request->data['redirect_to']));
        if (!$backLink) {
            $backLink = $router->getUrlByRoute(new Routing\Index());
        }



        $backLink = (isset($request->server['HTTP_REFERER'])) ? parse_url($request->server['HTTP_REFERER']) : false;

        foreach ($result['partners'] as $key => $partner) {
            $partnerMedia = $pointRepository->getMedia($partner['slug'], ['logo', 'marker']);

            $partners[$partner['slug']] = [
                'id' => $partner['slug'],
                'name' => $partner['name'],
                'logo' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'logo', '100x100')->url,
                'marker' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'marker', '31x40')->url
            ];
        }

        $result['points'] = array_map(function($point) use(&$pointRepository, &$partners, &$router, &$backLink) {
            return [
                'group' => ['id' => $point['partner']],
                'ui' => $point['uid'],
                'link' => $router->getUrlByRoute(
                    new Routing\ShopCard\Get($point['slug']),
//                    ['redirect_to' => $router->getUrlByRoute(new Routing\Shop\Index())]
                    ['redirect_to' => $backLink['path'].'?'.$backLink['query']]
                ),
                'slug' => $point['slug'],
                'address' => $point['address'],
                'regime' => $point['working_time'],
                'longitude' => isset($point['location'][0]) ? $point['location'][0] : null,
                'latitude' => isset($point['location'][1]) ? $point['location'][1] : null,
                'subway' => [[
                    'name' => isset($point['subway']['name']) ? $point['subway']['name'] : null,
                    'line' => [
                        'name' => isset($point['subway']['line_name']) ? $point['subway']['line_name'] : null,
                        'color' => isset($point['subway']['line_color']) ? $point['subway']['line_color'] : null,
                    ],
                ]],
                'logo' => $partners[$point['partner']]['logo'],
                'marker' => $partners[$point['partner']]['marker'],
                'name' => $partners[$point['partner']]['name']
            ];
        }, $result['points']);

        return new Http\JsonResponse([
            'data' => $result
        ]);
    }



    private function findPointsWithinRange($center) {
        $upperLeftCorner = [
            'longitude' => $center['longitude'] - (1 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] + (1 * (360/40075))
        ];

        $upperRightCorner = [
            'longitude' => $center['longitude'] + (1 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] + (1 * (360/40075))
        ];

        $bottomLeftCorner = [
            'longitude' => $center['longitude'] - (1 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] - (1 * (360/40075))
        ];

        $bottomRightCorner = [
            'longitude' => $center['longitude'] + (1 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] - (1 * (360/40075))
        ];

        return [implode(' ', $upperLeftCorner), implode(' ', $upperRightCorner), implode(' ', $bottomRightCorner), implode(' ', $bottomLeftCorner)];
    }
}