<?php

namespace EnterTerminal\Controller\ProductCatalog;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\Model\Context;
use EnterTerminal\Controller;
use EnterCurlQuery as Query;
use EnterModel as Model;
use EnterTerminal\Model\Page\ProductCatalog\Category as Page;

class Category {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter();

        // ид магазина
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);
        if (!$categoryId) {
            throw new \Exception('Не указан параметр categoryId');
        }

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

        // количество товаров на страницу
        $limit = (int)$request->query['limit'] ?: 10;

        // сортировка
        $sorting = null;
        if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
            $sorting = new Model\Product\Sorting();
            $sorting->token = trim((string)$request->query['sort']['token']);
            $sorting->direction = trim((string)$request->query['sort']['direction']);
        }

        // запрос магазина
        $shopItemQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopItemQuery);

        $curl->execute();

        // магазин
        $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        // фильтры в запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        // фильтр категории в http-запросе
        //$requestFilters[] = $filterRepository->getRequestObjectByCategory($category);

        $context = new Context\ProductCatalog();
        $context->mainMenu = false;
        $context->parentCategory = true;
        $controllerResponse = (new \EnterAggregator\Controller\ProductCatalog\ChildCategory())->execute(
            $shop->regionId,
            ['id' => $categoryId], // критерий получения категории товара
            $pageNum, // номер страницы
            $limit, // лимит
            $sorting, // сортировка
            $filterRepository, // репозиторий фильтров
            $requestFilters, // фильтры в http-запросе
            $context
        );

        // категория
        if (!$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
        }

        // страница
        $page = new Page();
        $page->category = $controllerResponse->category;
        $page->catalogConfig = $controllerResponse->catalogConfig;
        $page->products = $controllerResponse->products;
        $page->productCount = $controllerResponse->productIdPager->count;
        $page->filters = $controllerResponse->filters;
        $page->sortings = $controllerResponse->sortings;

        return new Http\JsonResponse($page);
    }
}