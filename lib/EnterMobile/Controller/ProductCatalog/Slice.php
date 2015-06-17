<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;
use EnterQuery;

class Slice {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $filterRepository = new Repository\Product\Filter();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен среза
        $sliceToken = (new \EnterRepository\Product\Slice())->getTokenByHttpRequest($request); // TODO: перенести в EnterMobile\Repository

        // токен категории
        $categoryToken = (new \EnterRepository\Product\Category())->getTokenByHttpRequest($request); // TODO: перенести в EnterMobile\Repository

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = (new \EnterRepository\Product())->getLimitByHttpRequest($request);

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);

        // запрос среза
        $sliceItemQuery = new Query\Product\Slice\GetItemByToken($sliceToken);
        $curl->prepare($sliceItemQuery);

        $curl->execute();

        // срез
        $slice = (new \EnterRepository\Product\Slice())->getObjectByQuery($sliceItemQuery);
        if (!$slice) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Срез товаров @%s не найден', $sliceToken));
        }

        // фильтры в http-запросе и настройках среза
        $baseRequestFilters = $filterRepository->getRequestObjectListByHttpRequest(new Http\Request($slice->filters));
        // AG-43: если выбрана категория, то удялять замороженные фильтры-категории
        if ($categoryToken) {
            foreach ($baseRequestFilters as $i => $baseRequestFilter) {
                if ('category' == $baseRequestFilter->token) {
                    unset($baseRequestFilters[$i]);
                }
            }
        }

        $baseRequestFilters[] = $filterRepository->getSliceRequestObjectBySlice($slice);

        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\ProductList();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->mainMenu = true;
        $controllerRequest->config->parentCategory = false;
        $controllerRequest->config->branchCategory = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->categoryCriteria = $categoryToken ? ['token' => $categoryToken] : []; // критерий получения категории товара
        $controllerRequest->pageNum = $pageNum;
        $controllerRequest->limit = $limit;
        $controllerRequest->sorting = $sorting;
        $controllerRequest->filterRepository = $filterRepository;
        $controllerRequest->baseRequestFilters = $baseRequestFilters;
        $controllerRequest->requestFilters = array_merge($requestFilters, $baseRequestFilters);
        $controllerRequest->userToken = (new \EnterMobile\Repository\User())->getTokenByHttpRequest($request);
        $controllerRequest->getCart = true;
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        if ($categoryToken && !$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\Slice\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = $controllerResponse->productUiPager->count; // TODO: передавать productUiPager
        $pageRequest->slice = $slice;
        $pageRequest->requestFilters = $requestFilters;
        $pageRequest->baseRequestFilters = $controllerResponse->baseRequestFilters;
        $pageRequest->filters = $controllerResponse->filters;
        $pageRequest->sorting = $controllerResponse->sorting;
        $pageRequest->sortings = $controllerResponse->sortings;
        $pageRequest->category = $controllerResponse->category;
        $pageRequest->categories = $controllerResponse->category ? $controllerResponse->category->children : $controllerResponse->categories;
        $pageRequest->catalogConfig = $controllerResponse->catalogConfig;
        $pageRequest->products = $controllerResponse->products;
        $pageRequest->buyBtnListing = $controllerResponse->buyBtnListing;

        // страница
        $page = new Page();
        (new Repository\Page\ProductCatalog\Slice())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/product-catalog/child-category/content',
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}