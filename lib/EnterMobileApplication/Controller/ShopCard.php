<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\ShopCard\Response;

    class ShopCard {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $shopId = trim((string)$request->query['shopId']);
            if (!$shopId) {
                throw new \Exception('Не указан параметр shopId');
            }

            // запрос магазина
            $shopItemQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            // магазин
            $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);

            if ($shop) {
                $shop->description = strip_tags($shop->description);
            }

            // ответ
            $response = new Response();
            $response->shop = $shop;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ShopCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Shop */
        public $shop;
    }
}