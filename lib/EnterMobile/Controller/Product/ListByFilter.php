<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Product\ListByFilter as Page;

class ListByFilter {
    use ConfigTrait, LoggerTrait, CurlTrait, MustacheRendererTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $filterRepository = new Repository\Product\Filter();

        // поисковая фраза
        if ($searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request)) {
            return (new Controller\Product\ListBySearchPhrase())->execute($request);
        }

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

        // основные фильтры
        $baseRequestFilters = [];
        // фильтр категории
        if ($categoryRequestFilter = $filterRepository->getCategoryRequestObjectByRequestList($requestFilters)) {
            $baseRequestFilters[] = $categoryRequestFilter;
        }
        // фильтр среза
        if ($sliceRequestFilter = $filterRepository->getSliceRequestObjectByRequestList($requestFilters)) {
            // запрос среза
            $sliceItemQuery = new Query\Product\Slice\GetItemByToken($sliceRequestFilter->value);
            $curl->prepare($sliceItemQuery)->execute();

            // срез
            $slice = (new \EnterRepository\Product\Slice())->getObjectByQuery($sliceItemQuery);
            if (!$slice) {
                $this->getLogger()->push(['type' => 'error', 'error' => ['code' => 0, 'message' => 'Срез товаров не найден'], 'sliceToken' => $sliceRequestFilter->value, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'critical']]);
            } else {
                foreach ($filterRepository->getRequestObjectListByHttpRequest(new Http\Request($slice->filters)) as $requestFilter) {
                    $baseRequestFilters[] = $requestFilter;
                    $requestFilters[] = $requestFilter;
                }
            }
        }

        // запрос фильтров
        $filterListQuery = null;
        if ((bool)$baseRequestFilters) {
            $filterListQuery = new Query\Product\Filter\GetList($filterRepository->dumpRequestObjectList($baseRequestFilters), $region->id);
            $curl->prepare($filterListQuery);
        }

        // запрос листинга идентификаторов товаров
        $productIdPagerQuery = new Query\Product\GetIdPager(
            $filterRepository->dumpRequestObjectList($requestFilters),
            $sorting,
            $region->id,
            ($pageNum - 1) * $limit,
            $limit
        );
        $curl->prepare($productIdPagerQuery);

        $curl->execute();

        // фильтры
        $filters = $filterListQuery ? $filterRepository->getObjectListByQuery($filterListQuery) : [];

        // листинг идентификаторов товаров
        $productIdPager = (new \EnterRepository\Product\IdPager())->getObjectByQuery($productIdPagerQuery);

        // запрос списка товаров
        $productListQuery = null;
        if ((bool)$productIdPager->ids) {
            $productListQuery = new Query\Product\GetListByIdList($productIdPager->ids, $region->id);
            $curl->prepare($productListQuery);
        }

        // запрос списка рейтингов товаров
        $ratingListQuery = null;
        if ($config->productReview->enabled && (bool)$productIdPager->ids) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($productIdPager->ids);
            $curl->prepare($ratingListQuery);
        }

        // запрос списка видео для товаров
        $videoGroupedListQuery = new Query\Product\Media\Video\GetGroupedListByProductIdList($productIdPager->ids);
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

        // удаление фильтров
        foreach ($filters as $i => $filter) {
            foreach ($baseRequestFilters as $requestFilter) {
                if ($requestFilter->token == $filter->token) {
                    unset($filters[$i]);
                }
            }
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Product\ListByFilter\Request();
        $pageRequest->pageNum = $pageNum + 1;
        $pageRequest->limit = $limit;
        $pageRequest->filters = $filters;
        $pageRequest->requestFilters = $requestFilters;
        $pageRequest->sorting = $sorting;
        $pageRequest->sortings = $sortings;
        $pageRequest->products = $productsById;
        $pageRequest->count = $productIdPager->count;

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