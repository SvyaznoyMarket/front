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
            return new Http\JsonResponse([
                'region' => [
                    'id' => '14974',
                    'ui' => '',
                    'kladrId' => '',
                    'code' => '',
                    'parentId' => '82',
                    'name' => 'Москва',
                    'token' => 'moskva-g',
                    'latitude' => 55.75578355133,
                    'longitude' => 37.617773222432,
                    'transportCompanyAvailable' => null,
                    'parent' => null,
                ],
            ]);
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