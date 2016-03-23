<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\ConfigTrait;

    class Discount {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            if (!(new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request)) {
                throw new \Exception('Не задан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!is_string($request->query['token'])) {
                throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!is_string($request->query['number'])) {
                throw new \Exception('Не задан параметр number', Http\Response::STATUS_BAD_REQUEST);
            }

            $number = $request->query['number'];

            $checkQuery = new \EnterQuery\Certificate\Check($number, '0000');
            $checkQuery->setTimeout(2 * $config->coreService->timeout);
            $curl->prepare($checkQuery);
            $curl->execute();

            $hasPin = false;

            try {
                $checkQuery->getResult();
            } catch (\Exception $e) {
                if (742 === $e->getCode()) {
                    $hasPin = true;
                }
            }

            return new Http\JsonResponse([
                'hasPin' => $hasPin,
            ]);
        }
    }
}