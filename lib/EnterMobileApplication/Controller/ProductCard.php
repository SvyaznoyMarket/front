<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
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
            return new Http\JsonResponse([
                'product' => null,
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var array|null */
        public $product;
    }
}