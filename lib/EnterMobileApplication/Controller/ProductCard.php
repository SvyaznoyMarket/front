<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\Model\Context\ProductCard as Context;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\ProductCard\Response;

    class ProductCard {
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

namespace EnterMobileApplication\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product|null */
        public $product;
    }
}