<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Point {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $pointRepository = new \EnterRepository\Point();
            $helper = new \Enter\Helper\Template();

            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            $ui = is_scalar($request->query['ui']) ? (string)$request->query['ui'] : null;

            if (empty($request->query['clientId'])) {
                throw new \Exception('Не указан параметр clientId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$userAuthToken) {
                throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$ui) {
                throw new \Exception('Не задан параметр ui', Http\Response::STATUS_BAD_REQUEST);
            }

//            $regionQuery = new Query\Region\GetItemById($regionId);
//            $curl->prepare($regionQuery);
//            $curl->execute();
//
//            $region = (new \EnterMobileApplication\Repository\Region())->getObjectByQuery($regionQuery);

            $pointItemQuery = new Query\Point\GetItemByUi($ui);
            $curl->prepare($pointItemQuery);

            $shopItemQuery = new Query\Shop\GetItemByUi($ui);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            $point = $pointItemQuery->getResult();
            $shop = $shopItemQuery->getResult();
            if (!$point) {
                throw new \Exception('Не удалось получить данные точки', Http\Response::STATUS_BAD_REQUEST);
            }

            return new Http\JsonResponse([
                'group' => [
                    'id' => $point['partner']['slug'],
                    'name' => $point['partner']['name'],
                    'media' => $pointRepository->getMedia($point['partner']['slug'], ['logo', 'marker']),
                ],
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
                'description' => isset($shop['description']) ? $helper->unescape(strip_tags($shop['description'])) : '',
                'phone' => isset($shop['phone']) ? $shop['phone'] : '',
                'walkWay' => isset($shop['way_walk']) ? $helper->unescape(strip_tags($shop['way_walk'])) : '',
                'carWay' => isset($shop['way_auto']) ? $helper->unescape(strip_tags($shop['way_auto'])) : '',
                'media' => new Model\MediaList(isset($shop['medias']) ? $shop['medias'] : []),
            ]);
        }
    }
}