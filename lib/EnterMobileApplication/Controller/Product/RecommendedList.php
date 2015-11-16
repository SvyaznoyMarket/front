<?php

namespace EnterMobileApplication\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Product\RecommendedList\Response;

    class RecommendedList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'recommendedProducts' => [
                    'alsoBought' => [],
                    'alsoViewed' => [],
                    'similar'    => [],
                ],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\Product\RecommendedList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product[] */
        public $recommendedProducts = [
            'alsoBought' => [],
            'alsoViewed' => [],
            'similar'    => [],
        ];
    }
}
