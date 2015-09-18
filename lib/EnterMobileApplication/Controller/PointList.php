<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class PointList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $pointRepository = new \EnterRepository\Point();

            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            if (empty($request->query['clientId'])) {
                throw new \Exception('Не указан параметр clientId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$userAuthToken) {
                throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);
            $curl->execute();

            $region = (new \EnterMobileApplication\Repository\Region())->getObjectByQuery($regionQuery);

            $pointItemQuery = new Query\Point\GetListFromScms($region->id);
            $curl->prepare($pointItemQuery);
            $curl->execute();

            $result = $pointItemQuery->getResult();
            return new Http\JsonResponse([
                'groups' => array_map(function($group) use(&$pointRepository) {
                    return [
                        'id' => $group['slug'],
                        'name' => $group['name'],
                        'media' => $pointRepository->getMedia($group['slug'], ['logo', 'marker']),
                    ];
                }, $result['partners']),
                'points' => array_map(function($point) use(&$pointRepository) {
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
                    ];
                }, $result['points']),
            ]);
        }
    }
}