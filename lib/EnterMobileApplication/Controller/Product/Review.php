<?php

namespace EnterMobileApplication\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller\Product\Review\Response;

    class Review {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([]);
        }
    }
}

namespace EnterMobileApplication\Controller\Product\Review {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Review[] */
        public $reviews = [];
        /** @var int */
        public $reviewCount;
    }
}
