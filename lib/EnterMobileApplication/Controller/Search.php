<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Search {
    use ProductListingTrait;
    use ConfigTrait, CurlTrait;

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
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // поисковая строка
        $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request, 'phrase');
        if (!$searchPhrase) {
            throw new \Exception('Не передана поисковая фраза phrase', Http\Response::STATUS_BAD_REQUEST);
        }

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

        // количество товаров на страницу
        $limit = (int)$request->query['limit'];
        if ($limit < 1) {
            throw new \Exception('limit не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
        }
        if ($limit > 40) {
            throw new \Exception('limit не должен быть больше 40', Http\Response::STATUS_BAD_REQUEST);
        }

        // сортировки
        $sortings = (new \EnterRepository\Product\Sorting())->getObjectList();

        // сортировка
        $sorting = null;
        if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
            $sorting = new Model\Product\Sorting();
            $sorting->token = trim((string)$request->query['sort']['token']);
            $sorting->direction = trim((string)$request->query['sort']['direction']);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        if (!$region) {
            throw new \Exception(sprintf('Регион #%s не найден', $regionId));
        }

        // фильтры в http-запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        $filterData = $filterRepository->dumpRequestObjectList($requestFilters);
        // фильтр поисковой фразы
        $requestFilters[] = $filterRepository->getRequestObjectBySearchPhrase($searchPhrase);

        // запрос фильтров
        $filterListQuery = new Query\Product\Filter\GetListBySearchPhrase($searchPhrase, $region->id);
        $curl->prepare($filterListQuery);

        // запрос результатов поиска
        $searchResultQuery = new Query\Search\GetItemByPhrase($searchPhrase, $filterData, $sorting, $region->id, ($pageNum - 1) * $limit, $limit);
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
                $region->id
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
            $productListQuery = new Query\Product\GetListByIdList($searchResult->productIds, $region->id);
            $curl->prepare($productListQuery);

            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                $searchResult->productIds,
                [
                    'media'       => true,
                    'media_types' => ['main'], // только главная картинка
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
        if ($productsByUi) {
            $productRepository->setDescriptionForListByListQuery($productsByUi, $descriptionListQuery);
        }

        // список рейтингов товаров
        if ($ratingListQuery) {
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        // список медиа для товаров
        //$productRepository->setMediaForObjectListByQuery($productsById, $descriptionListQuery);

        // ответ
        $response = [
            'searchPhrase' => $searchPhrase,
            'forcedMean'   => $searchResult->forcedMean,
            'productCount' => $searchResult->productCount,
            'products'     => $this->getProductList(array_values($productsById)),
            'filters'      => $this->getFilterList($filters),
            'sortings'     => $this->getSortingList($sortings),
        ];

        return new Http\JsonResponse($response);
    }
}
