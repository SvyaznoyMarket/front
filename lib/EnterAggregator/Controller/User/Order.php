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

    class Order {
        use ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(Order\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();
            $pointRepository = new Repository\Point();

            $response = new Order\Response();

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
                $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request->httpRequest, $router->getUrlByRoute(new Routing\Index()));
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

            // заказ
            $orderId = $request->httpRequest->query['orderId'];

            $user = new \EnterMobile\Repository\User();
            $token = $user->getTokenByHttpRequest($request->httpRequest);

            $orderQuery = new Query\Order\GetItemById('site', $token, $orderId);
            $curl->prepare($orderQuery);
            $curl->execute();
            $orderResult = $orderQuery->getResult();

            // информация по точке
            if ($orderResult['point_ui']) {
                $pointUI = $orderResult['point_ui'];
                $pointItemQuery = new Query\Point\GetItemByUi($pointUI);
                $curl->prepare($pointItemQuery)->execute();
                $point = $pointItemQuery->getResult();

                $pointResult =
                $point ? [
                    'ui' => $point['uid'],
                    'name' => $pointRepository->getName($point['partner']),
                    'media' => $pointRepository->getMedia($point['partner'], ['logo']),
                    'address' => $point['address'],
                    'regime' => $point['working_time'],
                    'longitude' => isset($point['location'][0]) ? $point['location'][0] : null,
                    'latitude' => isset($point['location'][1]) ? $point['location'][1] : null,
                    'subway' => [[
                        'name' => isset($point['subway']['name']) ? $point['subway']['name'] : null,
                        'line' => [
                            'name' => isset($point['subway']['line_name']) ? $point['subway']['line_name'] : null,
                            'color' => isset($point['subway']['line_color']) ? $point['subway']['line_color'] : null,
                        ],
                    ]],
                ] : null;

                $orderResult['point'] = $pointResult;
            }


            $productIds = [];
            $productMap = [];
            foreach ($orderResult['product'] as $key => $product) {
                $productIds[] = $product['id'];
                $productMap[$product['id']] = $key;
            }

            $productsInfo = new Query\Product\GetDescriptionListByIdList($productIds, ['media' => 1]);
            $curl->prepare($productsInfo);
            $curl->execute();

            $productsInfoResult = $productsInfo->getResult();

            foreach ($productsInfoResult as $key => $productInfo) {
                $coreId = $productInfo['core_id'];

                $orderResult['product'][$productMap[$coreId]]['image'] = $productInfo['medias'][0]['sources'][0]['url'];
                $orderResult['product'][$productMap[$coreId]]['name'] = $productInfo['name'];
            }

            $response->order = $orderResult;

            return $response;
        }

        /**
         * @return Order\Request
         */
        public function createRequest() {
            return new Order\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\Order {
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
        public $order;
        public $redirect;



    }
}

namespace EnterAggregator\Controller\User\Order\Request {
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