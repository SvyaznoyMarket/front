<?php

namespace EnterAggregator\Controller\User {
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

    class Order {
        use SecurityTrait,
            ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(Order\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();
            $session = $this->getSession();
            $pointRepository = new Repository\Point();

            $response = new Order\Response();
            $orderRepository = new \EnterRepository\Order();

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            $userToken = $this->getUserToken($session, $request->httpRequest);

            /* пользователь */
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request->httpRequest);
            $curl->prepare($userItemQuery);

            $curl->execute();

            $response->user = $this->getUser($userItemQuery);
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

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
            $token = $user->getTokenBySessionAndHttpRequest($session, $request->httpRequest);

            $orderQuery = new Query\Order\GetItemById('site', $token, $orderId);
            $curl->prepare($orderQuery);
            $curl->execute();

            $orderRepo = $orderRepository->getObjectByQuery($orderQuery);
            $orderRepository->setDeliveryTypeForObjectList([$orderRepo]);

            // точка самовывоза
            if ($orderRepo->point) {
                $pointItemQuery = new Query\Point\GetItemByUi($orderRepo->point->ui);
                $curl->prepare($pointItemQuery)->execute();
                $point = $pointItemQuery->getResult();

                $pointResult =
                $point ? [
                    'ui' => $point['uid'],
                    'name' => $point['partner']['name'],
                    'media' => $pointRepository->getMedia($point['partner']['slug'], ['logo']),
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

                $orderRepo->point = $pointResult;
            }

            // дополним инфу по товарам
            $productIds = [];
            foreach ($orderRepo->product as $product) {
                $productIds[] = $product->id;
            }

            $productListQuery = new Query\Product\GetListByIdList($productIds, $response->region->id);
            $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList($productIds, ['media' => 1]);
            $curl->prepare($productListQuery);
            $curl->prepare($productDescriptionListQuery);
            $curl->execute();
            
            /** @var \EnterModel\Order\Product[] $mappedProducts */
            $mappedProducts = [];
            foreach ($orderRepo->product as $product) {
                $mappedProducts[$product->id] = $product;
            }

            foreach ((new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery], [$productDescriptionListQuery]) as $productInfo) {
                $mappedProducts[$productInfo->id]->ui = $productInfo->ui;
                $mappedProducts[$productInfo->id]->name = $productInfo->name;
                $mappedProducts[$productInfo->id]->media = $productInfo->media;
            }

            $orderRepo->product = $mappedProducts;

            $response->order = $orderRepo;

            $response->userMenu = (new Repository\UserMenu())->getItems($userToken, $response->user);

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
        /** @var array */
        public $userMenu;



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