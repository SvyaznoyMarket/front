<?php

namespace EnterMobileApplication\Controller\Product\Review {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Product\Review\Create\Response;

    class Create {
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

namespace EnterMobileApplication\Controller\Product\Review\Create {
    use EnterModel as Model;

    class Response {}
}
