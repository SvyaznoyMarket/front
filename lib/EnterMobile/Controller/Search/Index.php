<?php

namespace EnterMobile\Controller\Search;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Page\Search\Index as Page;

class Index {
    use ConfigTrait, LoggerTrait, RouterTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $logger = $this->getLogger();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $filterRepository = new Repository\Product\Filter();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // поисковая строка
        $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request);
        if (!$searchPhrase) {
            return (new Controller\Redirect())->execute($request->server['HTTP_REFERER'] ?: $this->getRouter()->getUrlByRoute(new Routing\Index()), 302);
        }

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

        // запрос дерева категорий для меню
        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        $curl->execute();

        // листинг идентификаторов товаров
        try {
            $searchResult = (new \EnterRepository\Search())->getObjectByQuery($searchResultQuery);
        } catch (\Exception $e) {
            $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['region']]);

            return (new Controller\Redirect())->execute($request->server['HTTP_REFERER'] ?: $this->getRouter()->getUrlByRoute(new Routing\Index()), 302);
        }

        // фильтры
        $filters = $filterRepository->getObjectListByQuery($filterListQuery);
        $filters[] = new \EnterModel\Product\Filter([
            'filter_id' => 'q',
            'name'      => 'Поисковая строка',
            'type_id'   => \EnterModel\Product\Filter::TYPE_STRING,
            'options'   => [
                ['id' => null],
            ],
        ]);
        // добавление фильтров категории
        $filters = array_merge($filters, $filterRepository->getObjectListByCategoryList((new \EnterRepository\Product\Category())->getObjectListBySearchResult($searchResult)));

        // запрос списка товаров
        $productListQuery = null;
        if ((bool)$searchResult->productIds) {
            $productListQuery = new Query\Product\GetListByIdList($searchResult->productIds, $region->id);
            $curl->prepare($productListQuery);
        }

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        // запрос списка рейтингов товаров
        $ratingListQuery = null;
        if ($config->productReview->enabled && (bool)$searchResult->productIds) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($searchResult->productIds);
            $curl->prepare($ratingListQuery);
        }

        // запрос списка видео для товаров
        $descriptionListQuery = new Query\Product\GetDescriptionListByUiList($searchResult->productIds);
        $curl->prepare($descriptionListQuery);

        $curl->execute();

        // список товаров
        $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

        $productRepository->setLabelImageUrlPathForObjectList($productsById, 0);

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

        // список рейтингов товаров
        if ($ratingListQuery) {
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        // список медиа для товаров
        $productRepository->setMediaForObjectListByQuery($productsById, $descriptionListQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Search\Request();
        $pageRequest->searchPhrase = $searchPhrase;
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = $searchResult->productCount; // TODO: передавать searchResult
        $pageRequest->requestFilters = $requestFilters;
        $pageRequest->filters = $filters;
        $pageRequest->sorting = $sorting;
        $pageRequest->sortings = $sortings;
        $pageRequest->products = $productsById;
        $pageRequest->httpRequest = $request;

        // страница
        $page = new Page();
        (new Repository\Page\Search())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/search/content',
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}