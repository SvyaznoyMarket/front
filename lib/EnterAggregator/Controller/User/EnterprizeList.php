<?php

namespace EnterAggregator\Controller\User{
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\RouterTrait;
    use EnterAggregator\DateHelperTrait;
    use EnterMobile\Routing;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class EnterprizeList {
        use ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait,
            DateHelperTrait;

        public function execute(EnterprizeList\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();

            $response = new EnterprizeList\Response();

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
            $user = new \EnterMobile\Repository\User();
            try {

                $token = $user->getTokenByHttpRequest($request->httpRequest);

                // список купонов
                $couponListQuery = new Query\Coupon\GetListByUserToken($token);
                $couponListQuery->setTimeout(10 * $config->coreService->timeout);
                $curl->prepare($couponListQuery);

                // список лимитов серий купонов
                $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
                $seriesLimitListQuery->setTimeout(3 * $config->coreService->timeout);
                $curl->prepare($seriesLimitListQuery);

                // список серий купонов
                $seriesListQuery = new Query\Coupon\Series\GetList(/*$user->isEnterprizeMember ? '1' : null*/null);
                $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
                $curl->prepare($seriesListQuery);

                $curl->execute();

                $usedSeriesIds = [];
                $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
                foreach ($coupons as $coupon) {
                    $usedSeriesIds[] = $coupon->seriesId;
                }

                $almostReadyCoupons = array_values(
                    array_filter( // фильрация серий купонов
                        (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
                        function(Model\Coupon\Series $series) use (&$usedSeriesIds) {
                            return in_array($series->id, $usedSeriesIds); // только те серии купонов, которые есть у ранее полученых купонов
                        }
                    )
                );

                $couponsToResponse = [];
                $now = time();

                foreach ($almostReadyCoupons as $coupon) {
                    if ($now > strtotime($coupon->endAt)) continue;

                    $couponsToResponse[] = $coupon;
                }

                $response->coupons = $couponsToResponse;

            } catch(\Exception $e) {

            }

            $response->userMenu = (new Repository\UserMenu())->getItems();



            return $response;
        }

        /**
         * @return EnterprizeList\Request
         */
        public function createRequest() {
            return new EnterprizeList\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\EnterprizeList {
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
        public $coupons;
        /** @var array */
        public $userMenu;


    }
}

namespace EnterAggregator\Controller\User\EnterprizeList\Request {
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