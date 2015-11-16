<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Cart\Response;

    class Cart {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'sum' => 0,
                'products' => [],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\Cart {
    use EnterModel as Model;

    class Response {
        /** @var float */
        public $sum;
        /** @var Model\Product[] */
        public $products = [];
    }
}