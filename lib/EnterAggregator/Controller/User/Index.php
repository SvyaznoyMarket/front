<?php

namespace EnterAggregator\Controller\User{
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\RouterTrait;
    use EnterMobile\Routing;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class Index {
        use ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(Index\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();

            $response = new Index\Response();

            $userToken = (new \EnterMobile\Repository\User())->getTokenByHttpRequest($request->httpRequest);

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);
            $curl->execute();
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            /* пользователь */
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request->httpRequest);
            if ($userItemQuery) {
                $curl->prepare($userItemQuery);
                $curl->execute();
                $response->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
            } else {
                // редирект
                $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request->httpRequest, $router->getUrlByRoute(new Routing\User\Login()));
                // http-ответ
                $response->redirect = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);
            }

            $orderCountQuery = new Query\Order\GetListByUserToken($userToken, 0, 0);
            $curl->prepare($orderCountQuery);

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

            $response->userMenu = (new Repository\UserMenu())->getItems();
            if (isset($response->userMenu['orders'])) {
                try {
                    $response->userMenu['orders']['count'] = $orderCountQuery->getResult()['total'];
                } catch (\Exception $e) {}
            }

            return $response;
        }

        /**
         * @return Index\Request
         */
        public function createRequest() {
            return new Index\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\Index {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var Request/Config */
        public $config;
        /** @var string|null */
        public $userToken;
        public $httpRequest;

        public function __construct() {
            $this->config = new Request\Config();
        }
    }

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\MainMenu|null */
        public $mainMenu;
        /** @var array */
        public $user;
        /** @var \EnterModel\Cart|null */
        public $cart;
        public $redirect;
        /** @var array */
        public $userMenu;


    }
}

namespace EnterAggregator\Controller\User\Index\Request {
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