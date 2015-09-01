<?php

namespace EnterMobile\Controller\Shop;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Shops\Index as Page;

class GetList {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

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

        foreach ($result['partners'] as $key => $partner) {
            $partnerMedia = $pointRepository->getMedia($partner['slug'], ['logo', 'marker']);

            $partners[$partner['slug']] = [
                'id' => $partner['slug'],
                'name' => $partner['name'],
                'logo' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'logo', '100x100')->url,
                'marker' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'marker', '31x40')->url
            ];
        }

        $result['points'] = array_map(function($point) use(&$pointRepository, &$partners) {
            return [
                'group' => ['id' => $point['partner']],
                'ui' => $point['uid'],
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

        $result['points'] = array_slice($result['points'],0,10);

//
//
//
//
//
//        if (empty($postData)) {
//
//
//            $pq = new Query\Point\GetListFromScms($regionId);
//            $curl->prepare($pq);
//            $curl->execute();
//
//            $points = $pq->getResult();
//
//            $pointRepository = new \EnterRepository\Point();
//
//
//            if ($points)
//
//
//            $pointCoordinates = [];
//            foreach ($points['points'] as $point) {
//                switch($point['partner']) {
//                    case 'euroset':
//                        $partner = 'Евросеть';
//                        break;
//                    case 'svyaznoy':
//                        $partner = 'Связной';
//                        break;
//                    case 'pickpoint':
//                        $partner = 'Pickpoint';
//                        break;
//                    case 'hermes':
//                        $partner = 'Hermes';
//                        break;
//                    case 'enter':
//                    default:
//                        $partner = 'Enter';
//                        break;
//                }
//
//                $pointListMedia = $pointRepository->getMedia($point['partner'], ['logo', 'marker']);
//
//                $pointCoordinates[] = [
//                    'lat' => $point['location'][1],
//                    'long' => $point['location'][0],
//                    'partner' => $partner,
//                    'marker' => (new \EnterRepository\Media())->getSourceObjectByList($pointListMedia->photos, 'marker', '31x40')->url
//                ];
//            }
//
//            return new Http\JsonResponse([
//                'data' => [
//                    'points' => $pointCoordinates
//                ]
//            ]);
//
//
//        }
//
//
//
//        $searchPhrase = (isset($postData['phrase'])) ? $postData['phrase'] : false;
//
//        if (!$searchPhrase) {
//            return new Http\JsonResponse([
//                'data' => false
//            ]);
//        }
//
//        $coordinates = [];
//        try {
//            $yq = new Query\Yandex\GetCoordinatesByPhrase($searchPhrase);
//            $curl->prepare($yq);
//            $curl->execute();
//            $coordinates = $yq->getResult();
//
//            $angles = $this->findPointsWithinRange($coordinates);
//
//            $result = [
//                'center' => $coordinates,
//                'angles' => $angles
//            ];
//
//            $partners = null;
//            if (isset($postData['partners'])) {
//                $partners = $postData['partners'];
//            }
//
//            $pq = new Query\Point\GetListFromScms(null, null, $result['angles'], $partners);
//            $curl->prepare($pq);
//            $curl->execute();
//
//            $points = $pq->getResult();
//
//            $pointRepository = new \EnterRepository\Point();
//
//
//            $pointCoordinates = [];
//            foreach ($points['points'] as $point) {
//                switch($point['partner']) {
//                    case 'euroset':
//                        $partner = 'Евросеть';
//                        break;
//                    case 'svyaznoy':
//                        $partner = 'Связной';
//                        break;
//                    case 'pickpoint':
//                        $partner = 'Pickpoint';
//                        break;
//                    case 'hermes':
//                        $partner = 'Hermes';
//                        break;
//                    case 'enter':
//                    default:
//                        $partner = 'Enter';
//                        break;
//                }
//
//                $pointListMedia = $pointRepository->getMedia($point['partner'], ['logo', 'marker']);
//
//                $pointCoordinates[] = [
//                    'lat' => $point['location'][1],
//                    'long' => $point['location'][0],
//                    'partner' => $partner,
//                    'marker' => (new \EnterRepository\Media())->getSourceObjectByList($pointListMedia->photos, 'marker', '31x40')->url
//                ];
//            }
//
//        } catch (\Exception $e) {
//            print_r($e);
//        }
//
//        if (!isset($pointCoordinates)) {
//            $pointCoordinates = [];
//        }

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