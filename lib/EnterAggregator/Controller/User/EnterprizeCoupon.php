<?php

namespace EnterAggregator\Controller\User{
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\RouterTrait;
    use EnterMobile\Controller\SecurityTrait;
    use EnterMobile\Routing;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class EnterprizeCoupon {
        use
            SecurityTrait,
            ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(EnterprizeCoupon\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();
            $session = $this->getSession();

            $response = new EnterprizeCoupon\Response();

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            $userToken = $this->getUserToken($session, $request->httpRequest);

            /* пользователь */
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request->httpRequest);
            $curl->prepare($userItemQuery);

            $curl->execute();
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);
            $response->user = $this->getUser($userItemQuery);

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

            // купоны
            $coupon = (string)$request->httpRequest->query['coupon'];
            $couponQuery = new Query\Coupon\Series\GetItemByUi($coupon);
            $couponQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($couponQuery);
            $curl->execute();


            $response->coupon = $couponQuery->getResult();

            $response->userMenu = (new Repository\UserMenu())->getItems($userToken, $response->user);

            return $response;
        }

        /**
         * @return EnterprizeCoupon\Request
         */
        public function createRequest() {
            return new EnterprizeCoupon\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\EnterprizeCoupon {
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
        public $coupon;
        /** @var array */
        public $userMenu;


    }
}

namespace EnterAggregator\Controller\User\EnterprizeCoupon\Request {
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