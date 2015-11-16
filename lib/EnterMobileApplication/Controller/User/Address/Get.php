<?php

namespace EnterMobileApplication\Controller\User\Address {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Address\Get\Response;

    class Get {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'token' => '',
                'addresses' => [],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Address\Get {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Address[] */
        public $addresses = [];
    }
}