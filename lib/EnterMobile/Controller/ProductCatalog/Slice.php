<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\Model\Context;
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
        $limit = (new \EnterRepository\Product\Catalog\Config())->getLimitByHttpRequest($request);

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

        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

        $context = new Context\ProductCatalog();
        $context->mainMenu = true;
        $context->parentCategory = false;
        $context->branchCategory = true;
        $controllerResponse = (new \EnterAggregator\Controller\ProductList())->execute(
            $regionId,
            $categoryToken ? ['token' => $categoryToken] : [], // критерий получения категории товара
            $pageNum, // номер страницы
            $limit, // лимит
            $sorting, // сортировка
            $filterRepository, // репозиторий фильтров
            $baseRequestFilters,
            array_merge($requestFilters, $baseRequestFilters), // фильтры
            $context
        );

        if ($categoryToken && !$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }

        // базовые фильтры
        $baseRequestFilters = (new \EnterMobile\Repository\Product\Filter())->getRequestObjectListByHttpRequest(new Http\Request($slice->filters)); // FIXME !!!
        if ($controllerResponse->category && ($categoryRequestFilter = $filterRepository->getRequestObjectByCategory($controllerResponse->category))) {
            $baseRequestFilters[] = $categoryRequestFilter;
        }

        // список категорий
        $categoryListQuery = new Query\Product\Category\GetTreeList(
            $controllerResponse->region->id,
            null,
            $filterRepository->dumpRequestObjectList($baseRequestFilters),
            $controllerResponse->category ? $controllerResponse->category->id : null
        );
        $curl->prepare($categoryListQuery)->execute();

        /** @var Model\Product\Category[] $categories */
        $categories = [];
        try {
            $categoryListResult = $categoryListQuery->getResult();

            $children =
                $categoryToken
                ? (
                    isset($categoryListResult[0]['children'][0])
                    ? $categoryListResult[0]['children']
                    : []
                )
                : (
                    isset($categoryListResult[0])
                    ? $categoryListResult
                    : []
                )
            ;
            foreach ($children as $categoryItem) {
                if (!isset($categoryItem['uid'])) continue;

                $categories[] = new Model\Product\Category($categoryItem);
            }
        } catch(\Exception $e) {
            // TODO
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\Slice\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = $controllerResponse->productUiPager->count; // TODO: передавать productUiPager
        $pageRequest->slice = $slice;
        $pageRequest->requestFilters = $requestFilters;
        $pageRequest->baseRequestFilters = $baseRequestFilters;
        $pageRequest->filters = $controllerResponse->filters;
        $pageRequest->sorting = $controllerResponse->sorting;
        $pageRequest->sortings = $controllerResponse->sortings;
        $pageRequest->category = $controllerResponse->category;
        $pageRequest->categories = $categories;
        $pageRequest->catalogConfig = $controllerResponse->catalogConfig;
        $pageRequest->products = $controllerResponse->products;

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