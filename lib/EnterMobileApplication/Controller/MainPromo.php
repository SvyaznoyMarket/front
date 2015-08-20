<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\MainPromo\Response;

    class MainPromo {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                "promos" => [
                    [
                        "id" => "1953",
                        "type" => "Content",
                        "name" => "Обнови приложение!",
                        "url" => "http://www.enter.ru/mobile_apps",
                        "image" => "d3/431416.jpg",
                        "items" => [
                            [
                                "typeId" => 4,
                                "productId" => null,
                                "sliceId" => null,
                                "productCategoryId" => null,
                                "contentToken" => "mobile_apps"
                            ]
                        ]
                    ]
                ]
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\MainPromo {
    use EnterModel as Model;

    class Response {
        /** @var \EnterModel\Promo[] */
        public $promos = [];
    }
}