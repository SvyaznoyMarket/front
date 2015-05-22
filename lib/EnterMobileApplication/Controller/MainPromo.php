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

    class MainPromo {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $promoRepository = new \EnterRepository\Promo();

            // В будущей планируется ввести таргетирование по регионам, поэтому заранее закладываем получение региона от мобильных приложений
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            /*
            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionQuery);
            */

            // запрос баннеров
            $promoListQuery = new Query\Promo\GetList(['app-mobile']);
            $curl->prepare($promoListQuery);

            $curl->execute();

            // баннеры
            $promos = $promoRepository->getObjectListByQuery($promoListQuery);

            // ответ
            $response = new Response();
            $response->promos = $promos;

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