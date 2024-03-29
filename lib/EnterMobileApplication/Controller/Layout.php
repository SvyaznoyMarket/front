<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Layout\Response;

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

            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            /** @var \EnterQuery\User\GetItemByToken|null $userItemQuery */
            $userItemQuery = null;
            if (0 !== strpos($userAuthToken, 'anonymous-')) {
                $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
                $curl->prepare($userItemQuery);
            }

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            if ($userItemQuery) {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery, false);
            } else {
                $user = null;
            }

            // запрос дерева категорий для меню
            $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(0);
            $curl->prepare($categoryTreeQuery);

            // запрос меню
            $mainMenuQuery = new Query\MainMenu\GetItem();
            $curl->prepare($mainMenuQuery);

            $secretSalePromoListQuery = null;
            if ($user) {
                $secretSalePromoListQuery = new \EnterQuery\Promo\SecretSale\GetList();
                $curl->prepare($secretSalePromoListQuery);
            }

            $curl->execute();

            // меню
            $mainMenu = (new \EnterMobileApplication\Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery, $secretSalePromoListQuery && (bool)$secretSalePromoListQuery->getResult());

            // ответ
            $response = new Response();
            $response->region = $region;
            $response->mainMenu = $mainMenu;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Layout {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
        /** @var \EnterModel\MainMenu */
        public $mainMenu;
    }
}