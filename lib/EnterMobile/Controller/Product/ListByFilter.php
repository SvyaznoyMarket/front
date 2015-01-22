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
        $limit = $productRepository->getLimitByHttpRequest($request);

        // список сортировок
        $sortings = (new \EnterRepository\Product\Sorting())->getObjectList();

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);
        if (!$sorting) {
            $sorting = reset($sortings);
        }

        $categoryItemQuery = null;
        $category = null;
        if (is_string($request->query['category'])) {
            $categoryItemQuery = new Query\Product\Category\GetItemById($request->query['category'], $regionId);
            $curl->prepare($categoryItemQuery);

            $curl->execute();

            $category = (new \EnterRepository\Product\Category())->getObjectByQuery($categoryItemQuery);
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
        $productUiPagerQuery = new Query\Product\GetUiPager(
            $filterRepository->dumpRequestObjectList($requestFilters),
            $sorting,
            $region->id,
            ($pageNum - 1) * $limit,
            $limit,
            $categoryItemQuery ? (new \EnterRepository\Product\Category())->getConfigObjectByQuery($categoryItemQuery) : null
        );
        $curl->prepare($productUiPagerQuery);

        $curl->execute();

        // фильтры
        $filters = $filterListQuery ? $filterRepository->getObjectListByQuery($filterListQuery) : [];

        // листинг идентификаторов товаров
        $productUiPager = (new \EnterRepository\Product\UiPager())->getObjectByQuery($productUiPagerQuery);

        // запрос списка товаров
        $productListQuery = null;
        if ((bool)$productUiPager->uis) {
            $productListQuery = new Query\Product\GetListByUiList($productUiPager->uis, $region->id);
            $curl->prepare($productListQuery);
        }

        // запрос списка рейтингов товаров
        $ratingListQuery = null;
        if ($config->productReview->enabled && (bool)$productUiPager->uis) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($productUiPager->uis);
            $curl->prepare($ratingListQuery);
        }

        // запрос списка видео для товаров
        //$descriptionListQuery = new Query\Product\GetDescriptionListByUiList($productUiPager->uis);
        //$curl->prepare($descriptionListQuery);

        $curl->execute();

        // список товаров
        $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

        // список рейтингов товаров
        if ($ratingListQuery) {
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        // список медиа для товаров
        //$productRepository->setMediaForObjectListByQuery($productsById, $descriptionListQuery);

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
        $pageRequest->count = $productUiPager->count;
        $pageRequest->category = $category;

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