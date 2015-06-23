<?php

namespace EnterAggregator\Controller {

    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\AbTestTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class ProductList {
        use ConfigTrait, CurlTrait, LoggerTrait, AbTestTrait;

        /**
         * @param ProductList\Request $request
         * @return ProductList\Response
         * @throws \Exception
         */
        public function execute(ProductList\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();
            $productCategoryRepository = new Repository\Product\Category();

            // response
            $response = new ProductList\Response();

            // список сортировок
            $response->sortings = (new Repository\Product\Sorting())->getObjectList();

            // выбранная сортировка
            $response->sorting = $request->sorting;
            if (!$response->sorting) {
                $response->sorting = reset($response->sortings);
            }

            // выбранные фильтры
            $response->requestFilters = $request->requestFilters;

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($request->userToken && (0 !== strpos($request->userToken, 'anonymous-'))) {
                $userItemQuery = new Query\User\GetItemByToken($request->userToken);
                $curl->prepare($userItemQuery);
            }

            if ($request->cart) {
                $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($request->cart, $request->regionId);
                $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($request->cart, $request->regionId);
            }

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

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

            $categoryRootListQuery = null;
            if ((bool)$request->categoryCriteria) {
                // наличие категорий в данном регионе с учетом фильтров
                $categoryListQuery = new Query\Product\Category\GetAvailableList(
                    $request->categoryCriteria,
                    $response->region->id,
                    1,
                    $request->filterRepository->dumpRequestObjectList($request->baseRequestFilters)
                );
                $curl->prepare($categoryListQuery);

                // дерево категорий
                $categoryTreeQuery = new Query\Product\Category\GetTree($request->categoryCriteria, 1, true, true, true);
                $curl->prepare($categoryTreeQuery);

                // подробный запрос категории (seo, настройки сортировки, ...)
                $categoryItemQuery = null;
                if (!empty($request->categoryCriteria['token'])) {
                    $categoryItemQuery = new Query\Product\Category\GetItemByToken($request->categoryCriteria['token'],
                        $response->region->id
                    );
                } else if (!empty($request->categoryCriteria['id'])) {
                    $categoryItemQuery = new Query\Product\Category\GetItemById($request->categoryCriteria['id'], $response->region->id);
                } else if (!empty($request->categoryCriteria['ui'])) {
                    throw new \Exception('Не поддерживаемый критерий ui для категории');
                } else if (!empty($request->categoryCriteria['link'])) {
                    throw new \Exception('Не поддерживаемый критерий link для категории');
                }
                $curl->prepare($categoryItemQuery);

                $curl->execute();

                $response->category = $productCategoryRepository->getObjectByQuery($categoryItemQuery);
                // предки и дети категории
                if ($response->category && $categoryTreeQuery) {
                    $productCategoryRepository->setBranchForObjectByQuery($response->category, $categoryTreeQuery, $categoryListQuery);
                }

                // настройки каталога
                // FIXME: удалить
                $response->catalogConfig = $categoryItemQuery ? (new Repository\Product\Category())->getConfigObjectByQuery($categoryItemQuery) : null;
            } else {
                // список корневых категорий
                $categoryAvailableListQuery = new Query\Product\Category\GetAvailableList(
                    null,
                    $response->region->id,
                    0,
                    $request->filterRepository->dumpRequestObjectList($request->baseRequestFilters)
                );
                $curl->prepare($categoryAvailableListQuery)->execute();

                $categoryUis = [];
                foreach ($categoryAvailableListQuery->getResult() as $item) {
                    $item += ['id' => null, 'uid' => null, 'product_count' => null];

                    if (!$item['uid'] || !$item['product_count']) continue;
                    $categoryUis[] = (string)$item['uid'];
                }

                $categoryRootListQuery = (bool)$categoryUis ? new Query\Product\Category\GetListByUiList($categoryUis, $response->region->id) : [];
                if ($categoryRootListQuery) {
                    $curl->prepare($categoryRootListQuery);
                }
            }

            // базовые фильтры
            $response->baseRequestFilters = $request->baseRequestFilters;
            if ($response->category) {
                $response->baseRequestFilters[] = $request->filterRepository->getRequestObjectByCategory($response->category);
            }

            // запрос фильтров
            if (!$request->filterRequestFilters) {
                $request->filterRequestFilters = $response->baseRequestFilters;
            }

            $filterListQuery = null;
            if (
                ($request->config->loadFiltersForRootCategory || ($response->category && $response->category->parent)) &&
                ($request->config->loadFiltersForMiddleCategory || ($response->category && (!$response->category->parent || !$response->category->hasChildren)))
            ) {
                $filterListQuery = new Query\Product\Filter\GetList($request->filterRepository->dumpRequestObjectList($request->filterRequestFilters), $response->region->id);
                $curl->prepare($filterListQuery);
            }

            $curl->execute();

            // корневые категории
            $response->categories = $categoryRootListQuery ? (new \EnterRepository\Product\Category())->getObjectListByQuery($categoryRootListQuery) : [];

            // FIXME
            if ($request->config->isSlice && !$request->sorting) {
                $response->catalogConfig = new Model\Product\Category\Config();
                $response->catalogConfig->sortings = [
                    'in_shop' => 'desc',
                    'artem'   => 'desc',
                ];

                $sorting = clone $response->sorting;
                $sorting->token = 'default';
            } else {
                $sorting = $response->sorting;
            }

            // запрос листинга идентификаторов товаров
            $productUiPagerQuery = null;
            if ($request->config->loadProductsForRootCategory || ($response->category && $response->category->parent)) {
                $productUiPagerQuery = new Query\Product\GetUiPager(
                    array_merge(
                        $request->filterRepository->dumpRequestObjectList($response->requestFilters),
                        $request->filterRepository->dumpRequestObjectList($response->baseRequestFilters)
                    ),
                    $sorting,
                    $response->region->id,
                    ($request->pageNum - 1) * $request->limit,
                    $request->limit,
                    $response->catalogConfig
                );
                $curl->prepare($productUiPagerQuery);
            }

            // запрос дерева категорий для меню
            $rootCategoryTreeQuery = null;
            if ($request->config->mainMenu) {
                // запрос дерева категорий для меню
                $rootCategoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
                $curl->prepare($rootCategoryTreeQuery);
            }

            $curl->execute();

            if ($filterListQuery) {
                // фильтры
                $response->filters = $request->filterRepository->getObjectListByQuery($filterListQuery);
                // значения для фильтров
                $request->filterRepository->setValueForObjectList($response->filters, $response->requestFilters);
                
                // MSITE-2 Срезы товаров: удаление фильтров, которые уже есть в срезах
                foreach ($response->filters as $i => $filter) {
                    foreach ($response->baseRequestFilters as $requestFilter) {
                        if ($requestFilter->token == $filter->token) {
                            unset($response->filters[$i]);
                        }
                    }
                }
                
                $response->filters = array_values($response->filters);
            }

            // листинг идентификаторов товаров
            $response->productUiPager = $productUiPagerQuery ? (new Repository\Product\UiPager())->getObjectByQuery($productUiPagerQuery) : null;

            // запрос списка товаров
            $productListQueries = [];
            if ($response->productUiPager && $response->productUiPager->uis) {
                foreach (array_chunk($response->productUiPager->uis, $config->curl->queryChunkSize) as $uisInChunk) {
                    $productListQuery = new Query\Product\GetListByUiList($uisInChunk, $response->region->id);
                    $curl->prepare($productListQuery);
                    $productListQueries[] = $productListQuery;
                }
            }

            // запрос списка медиа для товаров
            $descriptionListQuery = null;
            if ($response->productUiPager && (bool)$response->productUiPager->uis) {
                $descriptionListQuery = new Query\Product\GetDescriptionListByUiList(
                    $response->productUiPager->uis,
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            // запрос доставки товаров
            $deliveryListQuery = null;
            if (false && $response->productUiPager && (bool)$response->productUiPager->uis) {
                $cartProducts = [];
                foreach ($response->productUiPager->uis as $productUi) {
                    $cartProducts[] = new Model\Cart\Product(['ui' => $productUi, 'quantity' => 1]);
                }

                if ((bool)$cartProducts) {
                    $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $response->region->id);
                    //$curl->prepare($deliveryListQuery); // TODO: удалить вообще - тормозит
                }
            }

            // запрос меню
            $mainMenuQuery = null;
            if ($request->config->mainMenu) {
                $mainMenuQuery = new Query\MainMenu\GetItem();
                $curl->prepare($mainMenuQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled && $response->productUiPager && (bool)$response->productUiPager->uis) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($response->productUiPager->uis);
                $curl->prepare($ratingListQuery);
            }

            // запрос на проверку товаров в избранном
            $favoriteListQuery = null;
            if ($request->config->favourite && $response->user && $response->productUiPager->uis) {
                $favoriteListQuery = new Query\User\Favorite\CheckListByUserUi($response->user->ui, $response->productUiPager->uis);
                $favoriteListQuery->setTimeout($config->crmService->timeout / 2);
                $curl->prepare($favoriteListQuery);
            }

            $curl->execute();

            // список товаров
            $productsById = $productRepository->getIndexedObjectListByQueryList($productListQueries);

            // медиа для товаров
            if ($descriptionListQuery) {
                $productRepository->setDescriptionForIdIndexedListByQueryList($productsById, [$descriptionListQuery]);
            }

            // доставка товаров
            if ($deliveryListQuery) {
                $productRepository->setDeliveryForObjectListByQuery($productsById, $deliveryListQuery);
            }

            // меню
            if ($mainMenuQuery && $rootCategoryTreeQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $rootCategoryTreeQuery);
            }

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // список магазинов, в которых есть товар
            if ($request->config->shopState) {
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
            }

            // товары в избранном
            try {
                if ($favoriteListQuery) {
                    $productRepository->setFavoriteForObjectListByQuery($productsById, $favoriteListQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $response->products = array_values($productsById);

            // AB тест
            $chosenListingType = $this->getAbTest()->getObjectByToken('product_listing')->chosenItem->token;

            if ($chosenListingType == 'old_listing') {
                $response->buyBtnListing = false;
            } else if ($chosenListingType == 'new_listing') {
                $response->buyBtnListing = true;
            }

            if (
                !(($request->config->loadSortingsForRootCategory || ($response->category && $response->category->parent)) &&
                ($request->config->loadSortingsForMiddleCategory || ($response->category && (!$response->category->parent || !$response->category->hasChildren))))
            ) {
                $response->sortings = [];
            }

            return $response;
        }

        /**
         * @return ProductList\Request
         */
        public function createRequest() {
            return new ProductList\Request();
        }
    }
}

namespace EnterAggregator\Controller\ProductList {
    use EnterModel as Model;
    use EnterRepository as Repository;

    class Request {
        /** @var string */
        public $regionId;
        /** @var array */
        public $categoryCriteria;
        /** @var int */
        public $pageNum;
        /** @var int */
        public $limit;
        /** @var Model\Product\Sorting|null */
        public $sorting;
        /** @var Repository\Product\Filter */
        public $filterRepository;
        /** @var Model\Product\RequestFilter[] */
        public $baseRequestFilters = [];
        /** @var Model\Product\RequestFilter[] */
        public $requestFilters = [];
        /** @var Request\Config */
        public $config;
        /** @var array */
        public $filterRequestFilters = [];
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
        /** @var Model\Product\Category|null */
        public $category;
        /** @var Model\Product\Category[] */
        public $categories = [];
        /** @var Model\Product\Category\Config */
        public $catalogConfig;
        /** @var Model\MainMenu|null */
        public $mainMenu;
        /** @var Model\User|null */
        public $user;
        /** @var Model\Product\Sorting[] */
        public $sortings = [];
        /** @var Model\Product\Sorting|null */
        public $sorting;
        /** @var Model\Product\Filter[] */
        public $filters = [];
        /** @var Model\Product\RequestFilter[] */
        public $baseRequestFilters = [];
        /** @var Model\Product\RequestFilter[] */
        public $requestFilters = [];
        /** @var Model\Product[] */
        public $products = [];
        /** @var Model\Product\UiPager|null */
        public $productUiPager;
        /** @var bool */
        public $buyBtnListing;
    }
}

namespace EnterAggregator\Controller\ProductList\Request {
    class Config {
        /**
         * Загружать главное меню
         *
         * @var bool
         */
        public $mainMenu = true;
        /**
         * Загружать родительскую категорию с ее потомками
         *
         * @var bool
         */
        public $parentCategory = false;
        /**
         * Загружать ветку категории
         *
         * @var bool
         */
        public $branchCategory = false;
        /**
         * @var bool
         */
		public $loadProductsForRootCategory = true;
        /**
         * @var bool
         */
        public $loadFiltersForRootCategory = true;
        /**
         * @var bool
         */
        public $loadSortingsForRootCategory = true;
        /**
         * @var bool
         */
		public $loadFiltersForMiddleCategory = true;
        /**
         * @var bool
         */
  		public $loadSortingsForMiddleCategory = true;
        /**
         * Загружать остатки товаров по магазинам
         *
         * @var bool
         */
        public $shopState = false;
        /**
         * Это срез товаров? // FIXME костыль
         *
         * @var bool
         */
        public $isSlice = false;
        /**
         * Проверять товары в избранном?
         *
         * @var bool
         */
        public $favourite = false;
    }
}