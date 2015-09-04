<?php

namespace EnterAggregator\Controller\User {
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\RouterTrait;
    use EnterMobile\Routing;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class Orders {
        use ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(Orders\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();

            $response = new Orders\Response();

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);
            $curl->execute();
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            /* пользователь */
            try {
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
                    return $response;
                }

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

                $response->userMenu = (new Repository\UserMenu())->getMenuItems();

                $user = new \EnterMobile\Repository\User();
                $token = $user->getTokenByHttpRequest($request->httpRequest);

                $ordersQuery = new Query\Order\GetListByUserToken($token, 0, 40);
                $ordersQuery->setTimeout(1.5 * $config->coreService->timeout);
                $ordersQuery->setRetry(3);
                $curl->prepare($ordersQuery);
                $curl->execute();

                $response->orders = $ordersQuery->getResult();
            } catch (\Exception $e) {

                $response->orders = [];
                return $response;
            }




            return $response;
        }

        /**
         * @return Orders\Request
         */
        public function createRequest() {
            return new Orders\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\Orders {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var Request/Config */
        public $config;
        /** @var string|null */
        public $userToken;
        /** @var \Enter\Http\Request */
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
        /** @var \EnterMobile\Repository\User*/
        public $user;
        /** @var \EnterModel\Cart|null */
        public $cart;
        /** @var array */
        public $orders;
        public $redirect;
        /** @var array */
        public $userMenu;



    }
}

namespace EnterAggregator\Controller\User\Orders\Request {
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