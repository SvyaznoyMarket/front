<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Layout\Response;

    class Layout {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'region' => [
                    "id" => "14974",
                    "ui" => null,
                    "parentId" => "82",
                    "name" => "Москва",
                    "token" => "moskva-g",
                    "latitude" => 55.7558,
                    "longitude" => 37.6178,
                    "transportCompanyAvailable" => false,
                    "parent" => null
                ],
                'mainMenu' => ['elements' => []],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\Layout {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
        /** @var \EnterModel\MainMenu */
        public $mainMenu;
    }
}