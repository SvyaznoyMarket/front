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
     * @deprecated Удалить в версии MAPI 1.5. Вместо данного метода следует использовать MainPage
     */
    class MainPromo {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // В будущей планируется ввести таргетирование по регионам, поэтому заранее закладываем получение региона от мобильных приложений
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $promoListQuery = new Query\Promo\GetList(['app-mobile']);
            $curl->prepare($promoListQuery);
            $curl->execute();

            $response = new Response();
            $response->promos = (new \EnterMobileApplication\Repository\Promo())->getObjectListByQuery($promoListQuery);

            return new Http\JsonResponse($response);
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