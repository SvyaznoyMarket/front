<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterMobileApplication\Controller\Index\Response;

    class Index {
        use ConfigTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            // ответ
            $response = new Response();
            $response->success = true;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Index {
    use EnterModel as Model;

    class Response {
        /** @var bool */
        public $success;
    }
}