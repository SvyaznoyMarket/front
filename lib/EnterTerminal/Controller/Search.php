<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\Search\Response;

    class Search {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\Response
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new \EnterRepository\Product();
            $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // поисковая строка
            $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request, 'phrase');
            if (!$searchPhrase) {
                throw new \Exception('Не передана поисковая фраза', Http\Response::STATUS_BAD_REQUEST);
            }

            // номер страницы
            $pageNum = (int)$request->query['page'] ?: 1;

            // количество товаров на страницу
            $limit = (int)$request->query['limit'] ?: 10;

            // сортировки
            $sortings = (new \EnterRepository\Product\Sorting())->getObjectList();

            // сортировка
            $sorting = null;
            if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
                $sorting = new Model\Product\Sorting();
                $sorting->token = trim((string)$request->query['sort']['token']);
                $sorting->direction = trim((string)$request->query['sort']['direction']);
            }

            // фильтры в http-запросе
            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
            $filterData = $filterRepository->dumpRequestObjectList($requestFilters);
            // фильтр поисковой фразы
            $requestFilters[] = $filterRepository->getRequestObjectBySearchPhrase($searchPhrase);

            // запрос фильтров
            $filterListQuery = new Query\Product\Filter\GetListBySearchPhrase($searchPhrase, $regionId);
            $curl->prepare($filterListQuery);

            // запрос результатов поиска
            $searchResultQuery = new Query\Search\GetItemByPhrase($searchPhrase, $filterData, $sorting, $regionId, ($pageNum - 1) * $limit, $limit);
            $curl->prepare($searchResultQuery);

            $curl->execute();

            // фильтры
            $filters = $filterRepository->getObjectListByQuery($filterListQuery);
            // значения для фильтров
            $filterRepository->setValueForObjectList($filters, $requestFilters);

            // листинг идентификаторов товаров
            $searchResult = (new \EnterRepository\Search())->getObjectByQuery($searchResultQuery);

            // TODO: убрать когда поиск будет возвращать картинки категорий
            $categoryListQuery =
                (bool)$searchResult->categories
                ? new Query\Product\Category\GetListByIdList(
                    array_map(function(Model\SearchResult\Category $category) { return $category->id; }, $searchResult->categories),
                    $regionId
                )
                : null;
            if ($categoryListQuery) {
                $curl->prepare($categoryListQuery)->execute();
            }

            // фильтры
            $filters = $filterRepository->getObjectListByQuery($filterListQuery);
            $filters[] = new Model\Product\Filter([
                'filter_id' => 'phrase',
                'name'      => 'Поисковая строка',
                'type_id'   => Model\Product\Filter::TYPE_STRING,
                'options'   => [
                    ['id' => null],
                ],
            ]);
            // добавление фильтров категории
            //$categories = (new Repository\Product\Category())->getObjectListBySearchResult($searchResult); // TODO: вернуть когда поиск будет возвращать картинки категорий
            $categories = $categoryListQuery ? (new \EnterRepository\Product\Category())->getObjectListByQuery($categoryListQuery) : [];
            $categoryFilters = $filterRepository->getObjectListByCategoryList($categories);
            $filters = array_merge($filters, $categoryFilters);

            // значения для фильтров
            $filterRepository->setValueForObjectList($filters, $requestFilters);

            // запрос списка товаров
            $descriptionListQuery = null;
            $productListQuery = null;
            if ((bool)$searchResult->productIds) {
                $productListQuery = new Query\Product\GetListByIdList($searchResult->productIds, $regionId, ['related' => false]);
                $curl->prepare($productListQuery);

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $searchResult->productIds,
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                        'tag'         => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled && (bool)$searchResult->productIds) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($searchResult->productIds);
                $curl->prepare($ratingListQuery);
            }

            // запрос списка видео для товаров
            //$descriptionListQuery = new Query\Product\GetDescriptionListByUiList($searchResult->productIds);
            //$curl->prepare($descriptionListQuery);

            $curl->execute();

            // список товаров
            $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

            // товары по ui
            $productsByUi = [];
            call_user_func(function() use (&$productsById, &$productsByUi) {
                foreach ($productsById as $product) {
                    $productsByUi[$product->ui] = $product;
                }
            });

            // медиа для товаров
            if ($productsByUi && $descriptionListQuery) {
                $productRepository->setDescriptionForListByListQuery($productsByUi, [$descriptionListQuery]);
            }

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // список медиа для товаров
            //$productRepository->setMediaForObjectListByQuery($productsById, $descriptionListQuery);

            // список магазинов, в которых есть товар
            try {
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
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            // ответ
            $response = new Response();
            $response->searchPhrase = $searchPhrase;
            $response->forcedMean = $searchResult->forcedMean;
            $response->products = array_values($productsById);
            $response->productCount = $searchResult->productCount;
            $response->filters = $filters;
            $response->sortings = $sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Search {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $searchPhrase;
        /** @var string|null */
        public $forcedMean;
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
        /** @var Model\Product\Sorting[] */
        public $sortings = [];
        /** @var Model\Product\Filter[] */
        public $filters = [];
    }
}