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
            return new Http\JsonResponse([
                'regions' => [
                    [
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
                ],
            ]);
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