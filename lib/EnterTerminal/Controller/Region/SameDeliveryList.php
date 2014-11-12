<?php

namespace EnterTerminal\Controller\Region {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterTerminal\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Region\SameDeliveryList\Response;

    class SameDeliveryList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // запрос региона
            $regionListQuery = new Query\Region\GetSameDeliveryListById($regionId);
            $curl->prepare($regionListQuery);

            $curl->execute();

            // регион
            $regions = (new Repository\Region())->getObjectListByQuery($regionListQuery);

            // ответ
            $response = new Response();
            $response->regions = $regions;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Region\SameDeliveryList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region[] */
        public $regions;
    }
}