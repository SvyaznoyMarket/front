<?php

namespace EnterAggregator\Controller {

    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\Model\Context;
    use EnterModel;
    use EnterModel\Product\RequestFilter;
    use EnterQuery as Query;
    use EnterQuery;
    use EnterRepository as Repository;
    use EnterModel as Model;
    use EnterRepository;

    class ProductList {
        use ConfigTrait, CurlTrait, LoggerTrait;

        /**
         * @param string $regionId
         * @param array $categoryCriteria
         * @param int $pageNum
         * @param int $limit
         * @param Model\Product\Sorting|null $sorting
         * @param Repository\Product\Filter $filterRepository
         * @param Model\Product\RequestFilter[] $baseRequestFilters
         * @param Model\Product\RequestFilter[] $requestFilters
         * @param Context\ProductCatalog $context
         * @param string|null $userToken
         * @throws \Exception
         * @return ProductList\Response
         */
        public function execute(
            $regionId,
            array $categoryCriteria,
            $pageNum,
            $limit,
            $sorting,
            Repository\Product\Filter $filterRepository,
            array $baseRequestFilters,
            array $requestFilters,
            Context\ProductCatalog $context,
            $userToken = null
        ) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();
            $productCategoryRepository = new Repository\Product\Category();

            // response
            $response = new ProductList\Response();

            // список сортировок
            $response->sortings = (new Repository\Product\Sorting())->getObjectList();

            // выбранная сортировка
            $response->sorting = $sorting;
            if (!$response->sorting) {
                $response->sorting = reset($response->sortings);
            }

            // выбранные фильтры
            $response->requestFilters = $requestFilters;

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($userToken && ($context->favourite)) {
                $userItemQuery = new Query\User\GetItemByToken($userToken);
                $curl->prepare($userItemQuery);
            }

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // пользователь
            $user = null;
            try {
                if ($userItemQuery) {
                    $user = (new Repository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $categoryRootListQuery = null;
            if ((bool)$categoryCriteria) {
                // наличие категорий в данном регионе с учетом фильтров
                $categoryListQuery = new Query\Product\Category\GetAvailableList($categoryCriteria,
                    $response->region->id, 1, $filterRepository->dumpRequestObjectList($baseRequestFilters)
                );
                $curl->prepare($categoryListQuery);

                // дерево категорий
                $categoryTreeQuery = new Query\Product\Category\GetTree($categoryCriteria, 1, true, true, true);
                $curl->prepare($categoryTreeQuery);

                // подробный запрос категории (seo, настройки сортировки, ...)
                $categoryItemQuery = null;
                if (!empty($categoryCriteria['token'])) {
                    $categoryItemQuery = new Query\Product\Category\GetItemByToken($categoryCriteria['token'],
                        $response->region->id
                    );
                } else if (!empty($categoryCriteria['id'])) {
                    $categoryItemQuery = new Query\Product\Category\GetItemById($categoryCriteria['id'],
                        $response->region->id
                    );
                } else if (!empty($categoryCriteria['ui'])) {
                    throw new \Exception('Не поддерживаемый критерий ui для категории');
                } else if (!empty($categoryCriteria['link'])) {
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
                    $filterRepository->dumpRequestObjectList($baseRequestFilters)
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
            $response->baseRequestFilters = $baseRequestFilters;
            if ($response->category) {
                $response->baseRequestFilters[] = $filterRepository->getRequestObjectByCategory($response->category);
            }

            // запрос фильтров
            $filterListQuery = new Query\Product\Filter\GetList($filterRepository->dumpRequestObjectList($response->baseRequestFilters), $response->region->id);
            $curl->prepare($filterListQuery);

            $curl->execute();

            // корневые категории
            $response->categories = $categoryRootListQuery ? (new \EnterRepository\Product\Category())->getObjectListByQuery($categoryRootListQuery) : [];

            // FIXME
            if ($context->isSlice && !$sorting) {
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
            if (
                !$context->productOnlyForLeafCategory
                || ($context->productOnlyForLeafCategory && $response->category && !$response->category->hasChildren)
            ) {
                $productUiPagerQuery = new Query\Product\GetUiPager(
                    array_merge(
                        $filterRepository->dumpRequestObjectList($response->requestFilters),
                        $filterRepository->dumpRequestObjectList($response->baseRequestFilters)
                    ),
                    $sorting,
                    $response->region->id,
                    ($pageNum - 1) * $limit,
                    $limit,
                    $response->catalogConfig
                );
                $curl->prepare($productUiPagerQuery);
            }

            // запрос дерева категорий для меню
            $rootCategoryTreeQuery = null;
            if ($context->mainMenu) {
                // запрос дерева категорий для меню
                $rootCategoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
                $curl->prepare($rootCategoryTreeQuery);
            }

            $curl->execute();

            // фильтры
            $response->filters = $filterRepository->getObjectListByQuery($filterListQuery);
            // значения для фильтров
            $filterRepository->setValueForObjectList($response->filters, $response->requestFilters);

            // листинг идентификаторов товаров
            $response->productUiPager = $productUiPagerQuery ? (new Repository\Product\UiPager())->getObjectByQuery($productUiPagerQuery) : null;

            // запрос списка товаров
            $productListQuery = null;
            if ($response->productUiPager && (bool)$response->productUiPager->uis) {
                $productListQuery = new Query\Product\GetListByUiList($response->productUiPager->uis, $response->region->id);
                $curl->prepare($productListQuery);
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
            if ($context->mainMenu) {
                $mainMenuQuery = new Query\MainMenu\GetItem();
                $curl->prepare($mainMenuQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled && $response->productUiPager && (bool)$response->productUiPager->uis) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($response->productUiPager->uis);
                $curl->prepare($ratingListQuery);
            }

            // запрос списка медиа для товаров
            $descriptionListQuery = null;
            if ($response->productUiPager && (bool)$response->productUiPager->uis) {
                //$descriptionListQuery = new Query\Product\GetDescriptionListByUiList($response->productUiPager->uis); // TODO: не реализовано на scms
                //$curl->prepare($descriptionListQuery);
            }

            // запрос на проверку товаров в избранном
            $favoriteListQuery = null;
            if ($context->favourite && $user && $response->productUiPager->uis) {
                $favoriteListQuery = new Query\User\Favorite\CheckListByUserUi($user->ui, $response->productUiPager->uis);
                $favoriteListQuery->setTimeout($config->crmService->timeout / 2);
                $curl->prepare($favoriteListQuery);
            }

            $curl->execute();

            // список товаров
            $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

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

            // список медиа для товаров
            if ($descriptionListQuery) {
                $productRepository->setMediaForObjectListByQuery($productsById, $descriptionListQuery);
            }

            // список магазинов, в которых есть товар
            if ($context->shopState) {
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

            // удаление фильтров
            foreach ($response->filters as $i => $filter) {
                foreach ($response->baseRequestFilters as $requestFilter) {
                    if ($requestFilter->token == $filter->token) {
                        unset($response->filters[$i]);
                    }
                }
            }
            $response->filters = array_values($response->filters);

            return $response;
        }
    }
}

namespace EnterAggregator\Controller\ProductList {
    use EnterModel as Model;

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
    }
}