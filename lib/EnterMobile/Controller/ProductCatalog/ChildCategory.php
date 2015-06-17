<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;

class ChildCategory {
    use ConfigTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $productCategoryRepository = new \EnterRepository\Product\Category();
        $filterRepository = new Repository\Product\Filter();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен категории
        $categoryToken = $productCategoryRepository->getTokenByHttpRequest($request);

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = (new \EnterRepository\Product())->getLimitByHttpRequest($request);

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);

        // фильтры в http-запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        // базовые фильтры
        $baseRequestFilters = [];
        //$baseRequestFilters[] = $filterRepository->getRequestObjectByCategory($category);

        // контроллер
        $controller = new \EnterAggregator\Controller\ProductList();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->mainMenu = true;
        $controllerRequest->config->parentCategory = false;
        $controllerRequest->config->branchCategory = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->categoryCriteria = ['token' => $categoryToken]; // критерий получения категории товара
        $controllerRequest->pageNum = $pageNum;
        $controllerRequest->limit = $limit;
        $controllerRequest->sorting = $sorting;
        $controllerRequest->filterRepository = $filterRepository;
        $controllerRequest->baseRequestFilters = $baseRequestFilters;
        $controllerRequest->requestFilters = $requestFilters;
        $controllerRequest->userToken = (new \EnterMobile\Repository\User())->getTokenByHttpRequest($request);
        $controllerRequest->getCart = true;

        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        if (!$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }

        //die(var_dump($controllerResponse->category));

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\ChildCategory\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = $controllerResponse->productUiPager->count; // TODO: передавать productUiPager
        $pageRequest->requestFilters = $controllerResponse->requestFilters;
        $pageRequest->baseRequestFilters = $controllerResponse->baseRequestFilters;
        $pageRequest->filters = $controllerResponse->filters;
        $pageRequest->sorting = $controllerResponse->sorting;
        $pageRequest->sortings = $controllerResponse->sortings;
        $pageRequest->category = $controllerResponse->category;
        $pageRequest->catalogConfig = $controllerResponse->catalogConfig;
        $pageRequest->products = $controllerResponse->products;
        $pageRequest->buyBtnListing = $controllerResponse->buyBtnListing;

        // страница
        $page = new Page();
        (new Repository\Page\ProductCatalog\ChildCategory())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/product-catalog/child-category/content', //'content' => file_get_contents($this->getConfig()->mustacheRenderer->templateDir . '/page/product-catalog/child-category/content.mustache'),
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}