<?php

namespace EnterMobileApplication\Controller\Region {

    use Enter\Http;
    use EnterMobileApplication\Controller\Region\Get\Response;
    use EnterModel\Region;
    use EnterQuery\Region\GetItemById;
    use EnterAggregator\CurlTrait;

    class Get {

        use CurlTrait;

        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            $response = new Response();

            $regionId = $request->query['regionId'];

            if (is_numeric($regionId)) {
                $query = new GetItemById($regionId);
                $curl->prepare($query)->execute();
                $result = $query->getResult();
                if (is_array($result)) $response->region = new Region($result);
            }

            return new Http\JsonResponse($response);
        }

    }

}

namespace EnterMobileApplication\Controller\Region\Get {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
    }
}