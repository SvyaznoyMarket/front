<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Product\ListByFilter as Page;

class ListBySearchPhrase {
    use ConfigTrait, CurlTrait, MustacheRendererTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $filterRepository = new Repository\Product\Filter();

        // поисковая строка
        $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request);

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = (new \EnterRepository\Product\Catalog\Config())->getLimitByHttpRequest($request);

        // список сортировок
        $sortings = (new \EnterRepository\Product\Sorting())->getObjectList();

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);
        if (!$sorting) {
            $sorting = reset($sortings);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        $curl->execute();

        // фильтры в запросе
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

        // листинг идентификаторов товаров
        $searchResult = (new \EnterRepository\Search())->getObjectByQuery($searchResultQuery);

        // фильтры
        $filters = $filterListQuery ? $filterRepository->getObjectListByQuery($filterListQuery) : [];
        // добавление фильтров категории
        $filters = array_merge($filters, $filterRepository->getObjectListByCategoryList((new \EnterRepository\Product\Category())->getObjectListBySearchResult($searchResult)));

        // запрос списка товаров
        $productListQuery = null;
        if ((bool)$searchResult->productIds) {
            $productListQuery = new Query\Product\GetListByIdList($searchResult->productIds, $region->id);
            $curl->prepare($productListQuery);
        }

        // запрос списка рейтингов товаров
        $ratingListQuery = null;
        if ($config->productReview->enabled && (bool)$searchResult->productIds) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($searchResult->productIds);
            $curl->prepare($ratingListQuery);
        }

        // запрос списка видео для товаров
        $videoGroupedListQuery = new Query\Product\Media\Video\GetGroupedListByProductIdList($searchResult->productIds);
        $curl->prepare($videoGroupedListQuery);

        $curl->execute();

        // список товаров
        $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

        // список рейтингов товаров
        if ($ratingListQuery) {
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        // список видео для товаров
        $productRepository->setVideoForObjectListByQuery($productsById, $videoGroupedListQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Product\ListByFilter\Request();
        $pageRequest->pageNum = $pageNum + 1;
        $pageRequest->limit = $limit;
        $pageRequest->filters = $filters;
        $pageRequest->requestFilters = $requestFilters;
        $pageRequest->sorting = $sorting;
        $pageRequest->sortings = $sortings;
        $pageRequest->products = $productsById;
        $pageRequest->count = $searchResult->productCount;

        // страница
        $page = new Page();
        (new Repository\Page\Product\ListByFilter())->buildObjectByRequest($page, $pageRequest);
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // http-ответ
        $response = new Http\JsonResponse([
            'result' => $page,
        ]);

        return $response;
    }
}