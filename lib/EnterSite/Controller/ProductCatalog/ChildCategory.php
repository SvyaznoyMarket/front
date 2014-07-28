<?php

namespace EnterSite\Controller\ProductCatalog;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\Model\Context;
use EnterSite\Controller;
use EnterSite\Repository;
use EnterQuery as Query;
use EnterSite\Model;
use EnterSite\Model\Page\ProductCatalog\ChildCategory as Page;

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
        $limit = (new \EnterRepository\Product\Catalog\Config())->getLimitByHttpRequest($request);

        // сортировка
        $sorting = (new \EnterRepository\Product\Sorting())->getObjectByHttpRequest($request); // TODO: перенести метод в \EnterSite\Repository

        // фильтры в http-запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        // фильтр категории в http-запросе
        //$requestFilters[] = $filterRepository->getRequestObjectByCategory($category);

        $context = new Context\ProductCatalog();
        $context->mainMenu = true;
        $context->parentCategory = false;
        $controllerResponse = (new \EnterAggregator\Controller\ProductCatalog\ChildCategory())->execute(
            $regionId,
            ['token' => $categoryToken], // критерий получения категории товара
            $pageNum, // номер страницы
            $limit, // лимит
            $sorting, // сортировка
            $filterRepository, // репозиторий фильтров
            $requestFilters, // фильтры в http-запросе
            $context
        );

        if (!$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }
        if ($controllerResponse->category->redirectLink) {
            return (new Controller\Redirect())->execute($controllerResponse->category->redirectLink . ((bool)$request->getQueryString() ? ('?' . $request->getQueryString()) : ''), Http\Response::STATUS_MOVED_PERMANENTLY);
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\ChildCategory\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = $controllerResponse->productIdPager->count; // TODO: передавать productIdPager
        $pageRequest->requestFilters = $controllerResponse->requestFilters;
        $pageRequest->filters = $controllerResponse->filters;
        $pageRequest->sorting = $controllerResponse->sorting;
        $pageRequest->sortings = $controllerResponse->sortings;
        $pageRequest->category = $controllerResponse->category;
        $pageRequest->catalogConfig = $controllerResponse->catalogConfig;
        $pageRequest->products = $controllerResponse->products;

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