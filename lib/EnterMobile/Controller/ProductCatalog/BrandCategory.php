<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\Model\Context;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;

class BrandCategory {
    use ConfigTrait, CurlTrait, RouterTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productCategoryRepository = new \EnterRepository\Product\Category();
        $filterRepository = new Repository\Product\Filter();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен категории
        $categoryToken = $productCategoryRepository->getTokenByHttpRequest($request);

        // токен бренда
        $brandToken = is_scalar($request->query['brandToken']) ? (string)$request->query['brandToken'] : null;

        $brandQuery = new Query\Brand\GetItemByToken($brandToken, $regionId);
        $curl->prepare($brandQuery)->execute();

        $brand = (new \EnterRepository\Brand())->getObjectByQuery($brandQuery);
        if (!$brand) {
            return (new Controller\Redirect())->execute($this->getRouter()->getUrlByRoute(new Routing\ProductCatalog\GetChildCategory($request->query['categoryPath'])), 302);
        }

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = (new \EnterRepository\Product\Catalog\Config())->getLimitByHttpRequest($request);

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);

        // фильтры в http-запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        // базовые фильтры
        $baseRequestFilters = [];
        $baseRequestFilters[] = $filterRepository->getBrandRequestObjectByBrand($brand);

        $context = new Context\ProductCatalog();
        $context->mainMenu = true;
        $context->parentCategory = false;
        $context->branchCategory = true;
        $controllerResponse = (new \EnterAggregator\Controller\ProductList())->execute(
            $regionId,
            ['token' => $categoryToken], // критерий получения категории товара
            $pageNum, // номер страницы
            $limit, // лимит
            $sorting, // сортировка
            $filterRepository, // репозиторий фильтров
            $baseRequestFilters, // базовые фильтры
            $requestFilters, // фильтры в http-запросе
            $context
        );

        if (!$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
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
        $pageRequest->baseRequestFilters = $controllerResponse->baseRequestFilters;
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