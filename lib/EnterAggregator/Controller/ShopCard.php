<?php

namespace EnterAggregator\Controller {
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class ShopCard {
        use ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait;

        public function execute(ShopCard\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();

            $response = new ShopCard\Response();

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);
            $curl->execute();
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            /* пользователь */
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request->httpRequest);
            if ($userItemQuery) {
                $curl->prepare($userItemQuery);
            }
            $curl->execute();
            $response->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);

            /* корзина */
            $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
            $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $request->regionId);
            $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $request->regionId);
            $curl->execute();
            (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);
            $response->cart = $cart;

            /* меню */
            $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
            $curl->prepare($categoryTreeQuery);

            $mainMenuQuery = new Query\MainMenu\GetItem();
            $curl->prepare($mainMenuQuery);
            $curl->execute();
            // меню
            if ($mainMenuQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);
            }

            $shopQuery = new \EnterQuery\Point\GetListFromScms($request->regionId, null, ['slugs' => [$request->shopToken]]);
            $curl->prepare($shopQuery);
            $curl->execute();

            $shopDescription = [];
            if ($shopQuery->getResult()) {
                $shopDescription = $shopQuery->getResult();
            }

            $response->pointDescription = $shopDescription;

            return $response;
        }

        /**
         * @return Shopcard\Request
         */
        public function createRequest() {
            return new ShopCard\Request();
        }
    }
}

namespace EnterAggregator\Controller\ShopCard {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var Request/Config */
        public $config;
        /** @var string|null */
        public $userToken;
        public $httpRequest;
        public $shopToken;

        public function __construct() {
            $this->config = new Request\Config();
        }
    }

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\MainMenu|null */
        public $mainMenu;
        public $user;
        public $cart;
        public $pointDescription;

    }
}

namespace EnterAggregator\Controller\ShopCard\Request {
    class Config {
        /**
         * Загружать главное меню
         *
         * @var bool
         */
        public $mainMenu = true;
        /**
         * Посылать event-запросы только для авторизованного пользователя
         *
         * @var bool
         */
        public $authorizedEvent = true;
    }
}