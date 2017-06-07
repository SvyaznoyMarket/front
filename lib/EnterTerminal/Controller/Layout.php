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

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $shopUi = is_scalar($request->query['shopUi']) ? (string)$request->query['shopUi'] : null;

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionItemQuery);
            if (!$region) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Регион #%s не найден', $regionId));
            }

            // запрос дерева категорий для меню
            $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(0);
            $curl->prepare($categoryTreeQuery);

            // запрос меню
            $mainMenuQuery = new Query\MainMenu\GetItem($shopUi);
            $curl->prepare($mainMenuQuery);

            $curl->execute();

            // меню
            $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery, $region, $config);

            // ответ
            $response = new Response();
            $response->region = $region;
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
        /** @var \EnterModel\MainMenu */
        public $mainMenu;
    }
}