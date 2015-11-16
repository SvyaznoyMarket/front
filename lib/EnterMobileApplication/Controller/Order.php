<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterRepository as Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Order\Response;

    class Order {
        use ConfigTrait, CurlTrait, LoggerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'order' => null,
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\Order {
    use EnterModel as Model;

    class Response {
        /** @var Model\Order|null */
        public $order;
    }
}