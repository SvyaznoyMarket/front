<?php

namespace EnterMobileApplication\Controller\Search {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Search\Autocomplete\Response;

    class Autocomplete {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'categories' => [],
                'products' => [],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\Search\Autocomplete {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $categories = [];
        /** @var array */
        public $products = [];
    }
}