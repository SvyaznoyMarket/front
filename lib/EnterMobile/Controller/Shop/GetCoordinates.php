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

class GetCoordinates {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        $postData = $request->data->all();

        if (empty($postData)) {
            $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

            $pq = new Query\Point\GetListFromScms($regionId);
            $curl->prepare($pq);
            $curl->execute();

            $points = $pq->getResult();

            $pointRepository = new \EnterRepository\Point();


            $pointCoordinates = [];
            foreach ($points['points'] as $point) {
                switch($point['partner']) {
                    case 'euroset':
                        $partner = 'Евросеть';
                        break;
                    case 'svyaznoy':
                        $partner = 'Связной';
                        break;
                    case 'pickpoint':
                        $partner = 'Pickpoint';
                        break;
                    case 'hermes':
                        $partner = 'Hermes';
                        break;
                    case 'enter':
                    default:
                        $partner = 'Enter';
                        break;
                }

                $pointListMedia = $pointRepository->getMedia($point['partner'], ['logo', 'marker']);

                $pointCoordinates[] = [
                    'lat' => $point['location'][1],
                    'long' => $point['location'][0],
                    'partner' => $partner,
                    'marker' => (new \EnterRepository\Media())->getSourceObjectByList($pointListMedia->photos, 'marker', '31x40')->url
                ];
            }

            return new Http\JsonResponse([
                'data' => [
                    'points' => $pointCoordinates
                ]
            ]);


        }



        $searchPhrase = (isset($postData['phrase'])) ? $postData['phrase'] : false;

        if (!$searchPhrase) {
            return new Http\JsonResponse([
                'data' => false
            ]);
        }

        $coordinates = [];
        try {
            $yq = new Query\Yandex\GetCoordinatesByPhrase($searchPhrase);
            $curl->prepare($yq);
            $curl->execute();
            $coordinates = $yq->getResult();

            $angles = $this->findPointsWithinRange($coordinates);

            $result = [
                'center' => $coordinates,
                'angles' => $angles
            ];

            $partners = null;
            if (isset($postData['partners'])) {
                $partners = $postData['partners'];
            }

            $pq = new Query\Point\GetListFromScms(null, null, $result['angles'], $partners);
            $curl->prepare($pq);
            $curl->execute();

            $points = $pq->getResult();

            $pointRepository = new \EnterRepository\Point();


            $pointCoordinates = [];
            foreach ($points['points'] as $point) {
                switch($point['partner']) {
                    case 'euroset':
                        $partner = 'Евросеть';
                        break;
                    case 'svyaznoy':
                        $partner = 'Связной';
                        break;
                    case 'pickpoint':
                        $partner = 'Pickpoint';
                        break;
                    case 'hermes':
                        $partner = 'Hermes';
                        break;
                    case 'enter':
                    default:
                        $partner = 'Enter';
                        break;
                }

                $pointListMedia = $pointRepository->getMedia($point['partner'], ['logo', 'marker']);

                $pointCoordinates[] = [
                    'lat' => $point['location'][1],
                    'long' => $point['location'][0],
                    'partner' => $partner,
                    'marker' => (new \EnterRepository\Media())->getSourceObjectByList($pointListMedia->photos, 'marker', '31x40')->url
                ];
            }

        } catch (\Exception $e) {
            print_r($e);
        }

        if (!isset($pointCoordinates)) {
            $pointCoordinates = [];
        }

        return new Http\JsonResponse([
            'data' => [
                'points' => $pointCoordinates,
                'coordinates' => $result
            ]
        ]);
    }



    private function findPointsWithinRange($center) {
        $upperLeftCorner = [
            'longitude' => $center['longitude'] - (2 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] + (2 * (360/40075))
        ];

        $upperRightCorner = [
            'longitude' => $center['longitude'] + (2 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] + (2 * (360/40075))
        ];

        $bottomLeftCorner = [
            'longitude' => $center['longitude'] - (2 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] - (2 * (360/40075))
        ];

        $bottomRightCorner = [
            'longitude' => $center['longitude'] + (2 * (360/ (cos($center['latitude'])*40075))),
            'latitude' => $center['latitude'] - (2 * (360/40075))
        ];

        return [implode(' ', $upperLeftCorner), implode(' ', $upperRightCorner), implode(' ', $bottomRightCorner), implode(' ', $bottomLeftCorner)];






    }
}