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

    class Favorites {
        use
            SecurityTrait,
            ConfigTrait,
            CurlTrait,
            LoggerTrait,
            SessionTrait,
            RouterTrait;

        public function execute(Favorites\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $router = $this->getRouter();

            $response = new Favorites\Response();

            /* регион */
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            $userToken = $this->getUserToken($request->httpRequest);

            /* пользователь */
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request->httpRequest);
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

            $favoriteQuery = new Query\User\Favorite\GetListByUserUi($response->user->ui);
            $curl->prepare($favoriteQuery);

            $curl->execute();

            $favorites = $favoriteQuery->getResult();

            $uis = [];
            foreach ($favorites['products'] as $favorite) {
                $uis[] = $favorite['uid'];
            }

            $productsQuery = new Query\Product\GetListByUiList($uis, $request->regionId);
            $curl->prepare($productsQuery);

            $productDescriptionListQuery = new Query\Product\GetDescriptionListByUiList($uis, [
                'media'       => true,
                'media_types' => ['main'],
            ]);
            $curl->prepare($productDescriptionListQuery);

            // запрос рейтинга для товаров
            $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($uis);
            $curl->prepare($ratingListQuery);

            $curl->execute();

            $productRepository = new \EnterRepository\Product();
            $productsById = $productRepository->getIndexedObjectListByQueryList([$productsQuery], [$productDescriptionListQuery]);

            if (isset($ratingListQuery)) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            $response->favoriteProducts = array_values($productsById);

            $response->userMenu = (new Repository\UserMenu())->getItems($userToken, $response->user);

            return $response;
        }

        /**
         * @return Favorites\Request
         */
        public function createRequest() {
            return new Favorites\Request();
        }
    }
}

namespace EnterAggregator\Controller\User\Favorites {
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
        public $favoriteProducts;
        public $redirect;
        /** @var array */
        public $userMenu;



    }
}

namespace EnterAggregator\Controller\User\Favorites\Request {
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