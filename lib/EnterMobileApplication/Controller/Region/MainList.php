<?php

namespace EnterMobileApplication\Controller\Region {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Region\MainList\Response;

    class MainList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $regionListQuery = new Query\Region\GetMainList();
            $curl->prepare($regionListQuery)->execute();

            $response->regions = (new \EnterRepository\Region())->getObjectListByQuery($regionListQuery);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Region\MainList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region[] */
        public $regions = [];
    }
}