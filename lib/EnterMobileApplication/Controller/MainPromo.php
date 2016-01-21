<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\MainPromo\Response;

    /**
     * @deprecated Удалить в версии MAPI 1.5. Вместо данного метода следует использовать MainCard
     */
    class MainPromo {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                "promos" => (new \EnterMobileApplication\Repository\Promo())->getUpdateStub(),
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\MainPromo {
    use EnterModel as Model;

    class Response {
        /** @var \EnterModel\Promo[] */
        public $promos = [];
    }
}