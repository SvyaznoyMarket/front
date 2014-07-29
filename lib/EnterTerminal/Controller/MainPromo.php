<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterTerminal\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\MainPromo\Response;

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

            // ид магазина
            $shopId = (new Repository\Shop())->getIdByHttpRequest($request);

            // запрос магазина
            $shopItemQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            // магазин
            $shop = (new Repository\Shop())->getObjectByQuery($shopItemQuery);
            if (!$shop) {
                throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
            }

            // запрос баннеров
            $promoListQuery = new Query\Promo\GetList($shop->regionId);
            $curl->prepare($promoListQuery);

            $curl->execute();

            // баннеры
            $promos = $promoRepository->getObjectListByQuery($promoListQuery);

            // ответ
            $response = new Response();
            $response->region = $shop->region;
            $response->shop = $shop;
            $response->promos = $promos;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\MainPromo {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
        /** @var Model\Shop */
        public $shop;
        /** @var \EnterModel\Promo[] */
        public $promos = [];
    }
}