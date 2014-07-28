<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterTerminal\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Layout\Response;

    class Layout {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

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

            // запрос дерева категорий для меню
            $categoryListQuery = new Query\Product\Category\GetTreeList($shop->regionId, 3);
            $curl->prepare($categoryListQuery);

            // запрос меню
            $mainMenuQuery = new Query\MainMenu\GetItem();
            $curl->prepare($mainMenuQuery);

            $curl->execute();

            // меню
            $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

            // ответ
            $response = new Response();
            $response->region = $shop->region;
            $response->shop = $shop;
            $response->mainMenu = $mainMenu;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Layout {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
        /** @var Model\Shop */
        public $shop;
        /** @var \EnterModel\MainMenu */
        public $mainMenu;
    }
}