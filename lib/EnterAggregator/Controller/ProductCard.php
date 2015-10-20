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
            $productItemQuery = null;
            if (!empty($request->productCriteria['id'])) {
                $productItemQuery = new Query\Product\GetItemById($request->productCriteria['id'], $response->region->id);
            } else if (!empty($request->productCriteria['token'])) {
                $productItemQuery = new Query\Product\GetItemByToken($request->productCriteria['token'], $response->region->id);
            } else if (!empty($request->productCriteria['ui'])) {
                //$productItemQuery = new Query\Product\GetItemByUi($request->productCriteria['ui'], $response->region->id);
            }
            if (!$productItemQuery) {
                throw new \Exception('Неверный критерий для получения товара');
            }
            $curl->prepare($productItemQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($request->userToken && (0 !== strpos($request->userToken, 'anonymous-')) && ($request->config->favourite)) {
                $userItemQuery = new Query\User\GetItemByToken($request->userToken);
                $curl->prepare($userItemQuery);
            }

            $curl->execute();

            // товар
            $response->product = $productRepository->getObjectByQuery($productItemQuery);
            if (!$response->product) {
                return $response;
            }

            // пользователь
            $user = null;
            try {
                if ($userItemQuery) {
                    $user = (new Repository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
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
                    ($request->config->authorizedEvent && $user) // или авторизованные события с пользователем, ...
                    || !$request->config->authorizedEvent // ... или неавторизованные события
                )
            ) {
                $productViewEventQuery = new Query\Event\PushProductView($response->product->ui, $user ? $user->ui : null);
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
            $accessoryDescriptionListQuery = null;
            if ((bool)$response->product->accessoryIds) {
                $accessoryListQuery = new Query\Product\GetListByIdList(array_slice($response->product->accessoryIds, 0, $config->product->itemsInSlider), $response->region->id);
                $curl->prepare($accessoryListQuery);

                $accessoryDescriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $response->product->accessoryIds,
                    [
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($accessoryDescriptionListQuery);
            }

            // запрос наборов
            $kitListQuery = null;
            $kitDescriptionListQuery = null;
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

                $kitDescriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $kitIds,
                    [
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($kitDescriptionListQuery);
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

            // ид товаров
            $productIds = array_merge([$response->product->id], (bool)$response->product->accessoryIds ? $response->product->accessoryIds : []);

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($productIds);
                $curl->prepare($ratingListQuery);
            }

            // запрос на проверку товаров в избранном
            $favoriteListQuery = null;
            if ($request->config->favourite && $user && $response->product->ui) {
                $favoriteListQuery = new Query\User\Favorite\CheckListByUserUi($user->ui, [$response->product->ui]);
                $favoriteListQuery->setTimeout($config->crmService->timeout / 2);
                $curl->prepare($favoriteListQuery);
            }

            // запрос трастфакторов товара
            $descriptionListQuery = new Query\Product\GetDescriptionListByUiList(
                [$response->product->ui],
                [
                    'trustfactor' => true,
                    'category'    => true,
                    'media'       => true,
                    'property'    => true,
                    'tag'         => true,
                    'seo'         => true,
                    'label'       => true,
                    'brand'       => true,
                ]
            );
            $curl->prepare($descriptionListQuery);

            $productModelListQuery = new Query\Product\Model\GetListByUiList([$response->product->ui], $response->region->id);
            $curl->prepare($productModelListQuery);

            // запрос настроек каталога
            $categoryItemQuery = null;
            if ($response->product->category && $response->product->category->ui) {
                $categoryItemQuery = new Query\Product\Category\GetItemByUi($response->product->category->ui, $request->regionId);
                $curl->prepare($categoryItemQuery);
            }

            // запрос доступности кредита
            $cart = new Model\Cart();
            (new Repository\Cart())->setProductForObject($cart, new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]));
            $paymentGroupListQuery = new Query\PaymentGroup\GetList($response->region->id, $cart, ['isCredit' => true]);
            $curl->prepare($paymentGroupListQuery);

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
            $kitProductsById = $kitListQuery ? $productRepository->getIndexedObjectListByQueryList([$kitListQuery], function(&$item) {
                // оптимизация
                if ($mediaItem = reset($item['media'])) {
                    $item['media'] = [$mediaItem];
                }
            }) : [];

            if ($kitDescriptionListQuery) {
                $productRepository->setDescriptionForListByListQuery($kitProductsById, $kitDescriptionListQuery);
            }

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
            if ($accessoryListQuery && $accessoryDescriptionListQuery) {
                $productRepository->setAccessoryRelationForObjectListByQuery([$response->product->id => $response->product], $accessoryListQuery);
                $productRepository->setDescriptionForListByListQuery($response->product->relation->accessories, $accessoryDescriptionListQuery);
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

            // трастфакторы товара
            $productRepository->setDescriptionForListByListQuery(
                [
                    $response->product->ui => $response->product
                ],
                $descriptionListQuery
            );

            if ($productModelListQuery) {
                $productRepository->setModelForListByListQueryList(
                    [$response->product],
                    [$productModelListQuery]
                );
            }

            // доступность кредита
            $response->hasCredit =
                ($config->credit->directCredit->enabled && $response->product->isBuyable && ($response->product->price >= $config->credit->directCredit->minPrice)) // TODO: удалить часть условия после готовности CORE-2035
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