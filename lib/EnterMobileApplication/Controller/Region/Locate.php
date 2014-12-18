<?php

namespace EnterMobileApplication\Controller\Region {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Region\Locate\Response;

    class Locate {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $ip = is_scalar($request->query['ip']) ? trim((string)$request->query['ip']) : null;

            $latitude = is_scalar($request->query['latitude']) ? trim((string)$request->query['latitude']) : null;
            $longitude = is_scalar($request->query['longitude']) ? trim((string)$request->query['longitude']) : null;

            if (!$ip && (!$latitude || !$longitude)) {
                throw new \Exception('Не передан ip или параметры latitude и longitude', Http\Response::STATUS_BAD_REQUEST);
            }

            $limit = (int)$request->query['limit'] ?: 10;
            if ($limit > 100) {
                $limit = 100;
            }

            $regionListQuery = $ip ? new Query\Region\GetListByIp($ip) : new Query\Region\GetListByCoordinates($latitude, $longitude);
            $regionListQuery->setTimeout($config->coreService->timeout * 1.5);
            $curl->prepare($regionListQuery)->execute();

            $i = 0;
            foreach ($regionListQuery->getResult() as $regionItem) {
                if ($i >= $limit) break;

                $region = new Model\Region($regionItem);

                $response->regions[] = $region;

                $i++;
            }

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Region\Locate {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region[] */
        public $regions = [];
    }
}