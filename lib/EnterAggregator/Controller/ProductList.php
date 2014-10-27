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
            Context\ProductCatalog $context
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

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос категории
            $categoryItemQuery = null;
            if (!empty($categoryCriteria['id'])) {
                //$categoryItemQuery = new Query\Product\Category\GetItemById($categoryCriteria['id'], $response->region->id);
                $categoryItemQuery = new Query\Product\Category\GetTreeItemById($categoryCriteria['id'], $response->region->id, null, $filterRepository->dumpRequestObjectList($baseRequestFilters));
            } else if (!empty($categoryCriteria['token'])) {
                $categoryItemQuery = new Query\Product\Category\GetItemByToken($categoryCriteria['token'], $response->region->id);
            } else if (!empty($categoryCriteria['ui'])) {
                //$categoryItemQuery = new Query\Product\Category\GetItemByUi($categoryCriteria['ui'], $response->region->id);
            }
            if ((bool)$categoryCriteria && !$categoryItemQuery) {
                throw new \Exception('Неверный критерий для получения категории товара');
            }

            if ($categoryItemQuery) {
                $curl->prepare($categoryItemQuery);
            }

            $curl->execute();

            // категория
            if ($categoryItemQuery) {
                $response->category = $productCategoryRepository->getObjectByQuery($categoryItemQuery);
            }

            // базовые фильтры
            $response->baseRequestFilters = $baseRequestFilters;
            if ($response->category) {
                $response->baseRequestFilters[] = $filterRepository->getRequestObjectByCategory($response->category);
            }

            // запрос фильтров
            $filterListQuery = new Query\Product\Filter\GetList($filterRepository->dumpRequestObjectList($response->baseRequestFilters), $response->region->id);
            $curl->prepare($filterListQuery);

            $ascendantCategoryItemQuery = null;
            $parentCategoryItemQuery = null;
            $branchCategoryItemQuery = null;

            if ($response->category) {
                if ($context->parentCategory) {
                    // запрос предка категории
                    $ascendantCategoryItemQuery = new Query\Product\Category\GetAscendantItemByCategoryObject($response->category, $response->region->id);
                    $curl->prepare($ascendantCategoryItemQuery);
                    // запрос родителя категории и его детей
                    if ($response->category->parentId) {
                        $parentCategoryItemQuery = new Query\Product\Category\GetTreeItemById($response->category->parentId, $response->region->id);
                        $curl->prepare($parentCategoryItemQuery);
                    }
                } else if ($context->branchCategory) {
                    // запрос предка категории
                    $branchCategoryItemQuery = new Query\Product\Category\GetBranchItemByCategoryObject($response->category, $response->region->id, $filterRepository->dumpRequestObjectList($baseRequestFilters));
                    $curl->prepare($branchCategoryItemQuery);
                }
            }

            // запрос настроек каталога
            $catalogConfigQuery = null;
            if ($response->category) {
                $catalogConfigQuery = new Query\Product\Catalog\Config\GetItemByProductCategoryUi($response->category->ui, $regionId);
                $curl->prepare($catalogConfigQuery);
            }

            $curl->execute();

            // настройки каталога
            $response->catalogConfig = $catalogConfigQuery ? (new Repository\Product\Catalog\Config())->getObjectByQuery($catalogConfigQuery) : null;

            // FIXME
            if ($context->isSlice && !$sorting) {
                $response->catalogConfig = new Model\Product\Catalog\Config();
                $response->catalogConfig->sortings = [
                    'in_shop' => 'desc',
                    'artem'   => 'desc',
                ];

                $sorting = clone $response->sorting;
                $sorting->token = 'default';
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
            $categoryListQuery = null;
            if ($context->mainMenu) {
                $categoryListQuery = new Query\Product\Category\GetTreeList($response->region->id, 3);
                $curl->prepare($categoryListQuery);
            }

            $curl->execute();

            // фильтры
            $response->filters = $filterRepository->getObjectListByQuery($filterListQuery);
            // значения для фильтров
            $filterRepository->setValueForObjectList($response->filters, $response->requestFilters);

            // предки и дети категории
            if ($branchCategoryItemQuery) {
                $productCategoryRepository->setBranchForObjectByQuery($response->category, $branchCategoryItemQuery);
            }
            // предки категории
            if ($ascendantCategoryItemQuery) {
                $response->category->ascendants = $productCategoryRepository->getAscendantListByQuery($ascendantCategoryItemQuery);
            }
            // родитель категории
            if ($parentCategoryItemQuery) {
                $response->category->parent = $parentCategoryItemQuery ? $productCategoryRepository->getObjectByQuery($parentCategoryItemQuery) : null;
            }

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

            $curl->execute();

            // список товаров
            $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

            // доставка товаров
            if ($deliveryListQuery) {
                $productRepository->setDeliveryForObjectListByQuery($productsById, $deliveryListQuery);
            }

            // меню
            if ($mainMenuQuery && $categoryListQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);
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
        /** @var Model\Product\Catalog\Config */
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