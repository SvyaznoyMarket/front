<?php

namespace EnterMobileApplication\Controller {

    use Enter\Curl\Client;
    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\TemplateHelperTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller\Content\Response;

    class Content {
        use ConfigTrait, CurlTrait, TemplateHelperTrait;

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

namespace EnterMobileApplication\Controller\Content {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $content;
        /** @var string */
        public $title;
    }
}