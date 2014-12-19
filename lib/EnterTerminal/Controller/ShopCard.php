<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\ShopCard\Response;

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
                throw new \Exception('Не указан параметр shopId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос магазина
            $shopItemQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            // магазин
            $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
            if (!$shop) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Магазин #%s не найден', $shopId));
            }

            $shop->description = strip_tags($shop->description);

            // ответ
            $response = new Response();
            $response->shop = $shop;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ShopCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Shop */
        public $shop;
    }
}