<?php

namespace EnterAggregator\Controller {
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class ProductCard {
        use ConfigTrait, CurlTrait, LoggerTrait;

        /**
         * @param ProductCard\Request $request
         * @return ProductCard\Response
         * @throws \Exception
         */
        public function execute(ProductCard\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();

            // response
            $response = new ProductCard\Response();

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос товара
            $productListQuery = null;
            $productDescriptionListQuery = null;
            $productDescriptionFilter = [
                'trustfactor' => true,
                'media'       => true,
                'category'    => true,
                'label'       => true,
                'brand'       => true,
                'property'    => true,
                'tag'         => true,
                'seo'         => true,
            ];
            $productModelListQuery = null;
            if (!empty($request->productCriteria['id'])) {
                $productListQuery = new Query\Product\GetListByIdList([$request->productCriteria['id']], $response->region->id);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList([$request->productCriteria['id']], $productDescriptionFilter);
                $productModelListQuery = new Query\Product\Model\GetListByIdList([$request->productCriteria['id']], $response->region->id);
            } else if (!empty($request->productCriteria['token'])) {
                $productListQuery = new Query\Product\GetListByTokenList([$request->productCriteria['token']], $response->region->id);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByTokenList([$request->productCriteria['token']], $productDescriptionFilter);
                $productModelListQuery = new Query\Product\Model\GetListByTokenList([$request->productCriteria['token']], $response->region->id);
            } else if (!empty($request->productCriteria['ui'])) {
                //$productListQuery = new Query\Product\GetListByUiList([$request->productCriteria['ui']], $response->region->id);
                //$productDescriptionListQuery = new Query\Product\GetDescriptionListByUiList([$request->productCriteria['ui']], $productDescriptionFilter);
                //$productModelListQuery = new Query\Product\Model\GetListByUiList([$request->productCriteria['ui']], $response->region->id);
            }
            if (!$productListQuery) {
                throw new \Exception('Неверный критерий для получения товара');
            }
            $curl->prepare($productListQuery);
            $curl->prepare($productDescriptionListQuery);
            $curl->prepare($productModelListQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($request->userToken && (0 !== strpos($request->userToken, 'anonymous-'))) {
                $userItemQuery = new Query\User\GetItemByToken($request->userToken);
                $curl->prepare($userItemQuery);
            }

            if ($request->cart) {
                $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($request->cart, $response->region->id);
                $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($request->cart, $response->region->id);
            }

            $curl->execute();

            // товар
            $response->product = $productRepository->getObjectByQueryList([$productListQuery], [$productDescriptionListQuery]);

            if ($productModelListQuery && $response->product) {
                $productRepository->setModelForListByListQueryList(
                    [$response->product],
                    [$productModelListQuery]
                );
            }

            if (!$response->product) {
                return $response;
            }

            // пользователь
            try {
                if ($userItemQuery) {
                    $response->user = (new Repository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            if ($request->cart) {
                (new \EnterRepository\Cart())->updateObjectByQuery($request->cart, $cartItemQuery, $cartProductListQuery);
            }

            // запрос дерева категорий для меню
            $categoryTreeQuery = null;
            if ($request->config->mainMenu) {
                $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
                $curl->prepare($categoryTreeQuery);
            }

            $productViewEventQuery = null;
            if (
                $config->eventService->enabled
                && (
                    ($request->config->authorizedEvent && $response->user) // или авторизованные события с пользователем, ...
                    || !$request->config->authorizedEvent // ... или неавторизованные события
                )
            ) {
                $productViewEventQuery = new Query\Event\PushProductView($response->product->ui, $response->user ? $response->user->ui : null);
                $curl->prepare($productViewEventQuery);
            }

            // запрос меню
            $mainMenuQuery = null;
            if ($request->config->mainMenu) {
                $mainMenuQuery = new Query\MainMenu\GetItem();
                $curl->prepare($mainMenuQuery);
            }

            // запрос отзывов товара
            $reviewListQuery = null;
            if ($config->productReview->enabled && $request->config->review) {
                if ($config->productReview->enabled) {
                    $reviewListQuery = new Query\Product\Review\GetListByProductId($response->product->id, 1, $config->productReview->itemsInCard);
                    $curl->prepare($reviewListQuery);
                }
            }

            // запрос аксессуаров товара
            $accessoryListQuery = null;
            if ((bool)$response->product->accessoryIds) {
                $accessoryListQuery = new Query\Product\GetListByIdList(array_slice($response->product->accessoryIds, 0, $config->product->itemsInSlider), $response->region->id);
                $curl->prepare($accessoryListQuery);
            }

            // запрос наборов
            $kitListQuery = null;
            $kits = $response->product->kit;
            if ((bool)$response->product->kit) {
                $kitIds = array_map(function(Model\Product\Kit $kit) {
                    return $kit->id;
                }, $response->product->kit);

                // наборы из линиии
                if ($response->product->line) {
                    $lineItemQuery = new Query\Product\Line\GetItemByToken($response->product->line->token, $response->region->id);
                    $curl->prepare($lineItemQuery)->execute();
                    if ($line = (new Repository\Product\Line())->getObjectByQuery($lineItemQuery)) {
                        $response->product->line = $line;
                    }

                    $kitIds = array_merge($kitIds, (array)$response->product->line->productIds);
                }

                // дополнительные товары из других наборов для расчета доставки
                $kitIds = array_values(array_unique($kitIds));
                foreach (array_diff($kitIds, array_map(function(Model\Product\Kit $kit) { return $kit->id; }, $response->product->kit)) as $kitId) {
                    $kits[] = new Model\Product\Kit(['id' => $kitId]);
                }

                $kitListQuery = new Query\Product\GetListByIdList($kitIds, $response->region->id);
                $curl->prepare($kitListQuery);
            }

            // запрос доставки товара
            $deliveryListQuery = null;
            if (($request->config->delivery || (bool)$response->product->kit) && $response->product->isBuyable) {
                $cartProducts = [];
                $cartProducts[] = new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]);
                foreach ($kits as $kit) {
                    $cartProducts[] = new Model\Cart\Product(['id' => $kit->id, 'quantity' => $kit->count ?: 1]);
                }

                $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $response->region->id);
                $curl->prepare($deliveryListQuery);
            }

            // ид связанных товаров
            $relatedIds = array_merge(
                (bool)$response->product->accessoryIds ? $response->product->accessoryIds : [],
                (bool)$response->product->kit ? array_map(function(Model\Product\Kit $kit) { return $kit->id; }, $response->product->kit) : []
            );

            // ид всех товаров
            $productIds = array_merge([$response->product->id], $relatedIds);

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($productIds);
                $curl->prepare($ratingListQuery);
            }

            // запрос на проверку товаров в избранном
            $favoriteListQuery = null;
            if ($request->config->favourite && $response->user && $response->product->ui) {
                $favoriteListQuery = new Query\User\Favorite\CheckListByUserUi($response->user->ui, [$response->product->ui]);
                $favoriteListQuery->setTimeout($config->crmService->timeout / 2);
                $curl->prepare($favoriteListQuery);
            }

            // запрос статических данных связанных товаров
            $relatedDescriptionListQuery = null;
            if ($relatedIds) {
                $relatedDescriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $relatedIds,
                    [
                        'trustfactor' => false,
                        'media'       => true,
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                        'property'    => true,
                        'tag'         => false,
                        'seo'         => false,
                    ]
                );
                $curl->prepare($relatedDescriptionListQuery);
            }

            $curl->execute();

            // запрос настроек каталога
            $categoryItemQuery = null;
            if ($response->product->category && $response->product->category->ui) {
                $categoryItemQuery = new Query\Product\Category\GetItemByUi($response->product->category->ui, $response->region->id);
                $curl->prepare($categoryItemQuery);
            }

            // запрос доступности кредита
            $paymentGroupListQuery = null;
            if ($config->credit->directCredit->enabled) {
                $cart = new Model\Cart();
                (new Repository\Cart())->setProductForObject($cart, new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]));
                $paymentGroupListQuery = new Query\PaymentGroup\GetList($response->region->id, $cart, ['isCredit' => true]);
                $curl->prepare($paymentGroupListQuery);
            }

            $curl->execute();

            // товары в избранном
            try {
                if ($favoriteListQuery) {
                    $productRepository->setFavoriteForObjectListByQuery([$response->product], $favoriteListQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            // меню
            if ($mainMenuQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);
            }

            // отзывы товара
            $response->product->reviews = $reviewListQuery ? (new Repository\Product\Review())->getObjectListByQuery($reviewListQuery) : [];

            // наборы
            $kitProductsById = $kitListQuery ? $productRepository->getIndexedObjectListByQueryList([$kitListQuery], [$relatedDescriptionListQuery]) : [];
            foreach ($kitProductsById as $kitProduct) {
                $kitProduct->kitCount = 0;
            }

            foreach ($response->product->kit as $kit) {
                /** @var Model\Product|null $kiProduct */
                $kiProduct = isset($kitProductsById[$kit->id]) ? $kitProductsById[$kit->id] : null;
                if (!$kiProduct) continue;

                $kiProduct->kitCount = $kit->count; // FIXME
            }
            $response->product->relation->kits = array_values($kitProductsById);

            // аксессуары
            if ($accessoryListQuery) {
                $productRepository->setAccessoryRelationForObjectListByQuery([$response->product->id => $response->product], $accessoryListQuery, $relatedDescriptionListQuery);
            }

            // группированные товары
            $productsById = [];
            foreach (array_merge([$response->product], $response->product->relation->accessories, $kitProductsById) as $iProduct) {
                /** @var Model\Product $iProduct */
                $productsById[$iProduct->id] = $iProduct;
            }

            // доставка товара
            if ($deliveryListQuery) {
                $productRepository->setDeliveryForObjectListByQuery($productsById, $deliveryListQuery);
            }

            // категории аксессуаров
            $response->accessoryCategories = (new Repository\Product\Category())->getIndexedObjectListByProductListAndTokenList($response->product->relation->accessories, $response->catalogConfig ? $response->catalogConfig->accessoryCategoryTokens : []);

            // список магазинов, в которых есть товар
            $shopIds = [];
            foreach ($productsById as $product) {
                foreach ($product->stock as $stock) {
                    if (!$stock->shopId) continue;

                    $shopIds[] = $stock->shopId;
                }
            }
            if ((bool)$shopIds) {
                $shopListQuery = new Query\Shop\GetListByIdList($shopIds);
                $curl->prepare($shopListQuery);

                $curl->execute();

                foreach ($productsById as $product) {
                    $shopStatesByShopId = [];
                    foreach ($product->stock as $stock) {
                        if ($stock->shopId && (($stock->showroomQuantity + $stock->quantity) > 0)) {
                            $shopState = new Model\Product\ShopState();
                            $shopState->quantity = $stock->quantity;
                            $shopState->showroomQuantity = $stock->showroomQuantity;
                            $shopState->isInShowroomOnly = !$shopState->quantity && ($shopState->showroomQuantity > 0);

                            $shopStatesByShopId[$stock->shopId] = $shopState;
                        }
                    }
                    if ((bool)$shopStatesByShopId) {
                        $productRepository->setShopStateForObjectListByQuery([$product->id => $product], $shopStatesByShopId, $shopListQuery);
                    }
                }
            }

            // настройки каталога
            $response->catalogConfig = $categoryItemQuery ? (new Repository\Product\Category())->getConfigObjectByQuery($categoryItemQuery) : null;

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // доступность кредита
            $response->hasCredit =
                ($paymentGroupListQuery && $config->credit->directCredit->enabled && $response->product->isBuyable && ($response->product->price >= $config->credit->directCredit->minPrice)) // TODO: удалить часть условия после готовности CORE-2035
                ? (new Repository\PaymentGroup())->checkCreditObjectByListQuery($paymentGroupListQuery)
                : false;

            return $response;
        }

        /**
         * @return ProductCard\Request
         */
        public function createRequest() {
            return new ProductCard\Request();
        }
    }
}

namespace EnterAggregator\Controller\ProductCard {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var array */
        public $productCriteria;
        /** @var Request\Config */
        public $config;
        /** @var string|null */
        public $userToken;
        /** @var \EnterModel\Cart|null */
        public $cart;

        public function __construct() {
            $this->config = new Request\Config();
        }
    }

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\Product|null */
        public $product;
        /** @var Model\Product\Category[] */
        public $accessoryCategories = [];
        /** @var Model\Product\Category\Config */
        public $catalogConfig;
        /** @var Model\MainMenu|null */
        public $mainMenu;
        /** @var Model\User|null */
        public $user;
        /** @var bool */
        public $hasCredit;
    }
}

namespace EnterAggregator\Controller\ProductCard\Request {
    class Config {
        /**
         * Загружать главное меню
         *
         * @var bool
         */
        public $mainMenu = true;
        /**
         * Загружать отзывы
         *
         * @var bool
         */
        public $review = false;
        /**
         * Загружать доставку
         *
         * @var bool
         */
        public $delivery = true;
        /**
         * Проверять товары в избранном
         *
         * @var bool
         */
        public $favourite = false;
        /**
         * Посылать event-запросы только для авторизованного пользователя
         *
         * @var bool
         */
        public $authorizedEvent = true;
    }
}