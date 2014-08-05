<?php

namespace EnterAggregator\Controller {

    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
    use EnterModel;
    use EnterModel\Product\RequestFilter;
    use EnterQuery as Query;
    use EnterQuery;
    use EnterRepository as Repository;
    use EnterModel as Model;
    use EnterRepository;

    class ProductList {
        use ConfigTrait, CurlTrait;

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
                $categoryItemQuery = new Query\Product\Category\GetTreeItemById($categoryCriteria['id'], $response->region->id, $filterRepository->dumpRequestObjectList($baseRequestFilters));
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

            $categoryAdminItemQuery = null;
            if (!empty($categoryCriteria['token']) && $config->adminService->enabled) {
                $categoryAdminItemQuery = new Query\Product\Category\GetAdminItemByToken($categoryCriteria['token'], $response->region->id);
                $curl->prepare($categoryAdminItemQuery);
            }

            $curl->execute();

            // категория
            if ($categoryItemQuery) {
                $response->category = $productCategoryRepository->getObjectByQuery($categoryItemQuery, $categoryAdminItemQuery);
                if (!$response->category) {
                    // костыль для ядра
                    $categoryUi = isset($categoryAdminItemQuery->getResult()['ui']) ? $categoryAdminItemQuery->getResult()['ui'] : null;
                    $categoryItemQuery = $categoryUi ? new Query\Product\Category\GetItemByUi($categoryUi, $response->region->id) : null;

                    if ($categoryItemQuery) {
                        $curl->prepare($categoryItemQuery)->execute();
                        $response->category = $productCategoryRepository->getObjectByQuery($categoryItemQuery, $categoryAdminItemQuery);
                    }
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
                } else {
                    // запрос предка категории
                    $branchCategoryItemQuery = new Query\Product\Category\GetBranchItemByCategoryObject($response->category, $response->region->id, $filterRepository->dumpRequestObjectList($baseRequestFilters));
                    $curl->prepare($branchCategoryItemQuery);
                }
            }

            // запрос листинга идентификаторов товаров
            $productIdPagerQuery = new Query\Product\GetIdPager(
                array_merge(
                    $filterRepository->dumpRequestObjectList($response->requestFilters),
                    $filterRepository->dumpRequestObjectList($response->baseRequestFilters)
                ),
                $response->sorting,
                $response->region->id,
                ($pageNum - 1) * $limit,
                $limit
            );
            $curl->prepare($productIdPagerQuery);

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
            $response->productIdPager = (new Repository\Product\IdPager())->getObjectByQuery($productIdPagerQuery);

            // запрос списка товаров
            $productListQuery = null;
            if ((bool)$response->productIdPager->ids) {
                $productListQuery = new Query\Product\GetListByIdList($response->productIdPager->ids, $response->region->id);
                $curl->prepare($productListQuery);
            }

            // запрос доставки товаров
            $deliveryListQuery = null;
            if ((bool)$response->productIdPager->ids) {
                $cartProducts = [];
                foreach ($response->productIdPager->ids as $productId) {
                    $cartProducts[] = new Model\Cart\Product(['id' => $productId, 'quantity' => 1]);
                }

                if ((bool)$cartProducts) {
                    $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $response->region->id);
                    $curl->prepare($deliveryListQuery);
                }
            }

            // запрос меню
            $mainMenuQuery = null;
            if ($context->mainMenu) {
                $mainMenuQuery = new Query\MainMenu\GetItem();
                $curl->prepare($mainMenuQuery);
            }

            // запрос настроек каталога
            $catalogConfigQuery = null;
            if ($response->category) {
                $catalogConfigQuery = new Query\Product\Catalog\Config\GetItemByProductCategoryObject(array_merge($response->category->ascendants, [$response->category]));
                $curl->prepare($catalogConfigQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled && (bool)$response->productIdPager->ids) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($response->productIdPager->ids);
                $curl->prepare($ratingListQuery);
            }

            // запрос списка видео для товаров
            $videoGroupedListQuery = new Query\Product\Media\Video\GetGroupedListByProductIdList($response->productIdPager->ids);
            $curl->prepare($videoGroupedListQuery);

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

            // настройки каталога
            $response->catalogConfig = $catalogConfigQuery ? (new Repository\Product\Catalog\Config())->getObjectByQuery($catalogConfigQuery) : null;

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // список видео для товаров
            $productRepository->setVideoForObjectListByQuery($productsById, $videoGroupedListQuery);

            $response->products = array_values($productsById);

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
        /** @var Model\Product\IdPager|null */
        public $productIdPager;
    }
}