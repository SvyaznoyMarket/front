<?php

namespace EnterMobile\Controller\ProductSet;

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
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;
use EnterQuery;

class Index {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $barcodes = explode(',', is_scalar($request->query['productBarcodes']) ? $request->query['productBarcodes'] : '');
        $barcodes = array_filter($barcodes, function($barcode) { $barcode = trim($barcode); return !empty($barcode); });
        if (!(bool)$barcodes) {
            return (new Controller\Error\NotFound())->execute($request, 'Не переданы баркоды товаров');
        }
        $barcodes = array_slice($barcodes, 0, $config->product->itemPerPage * 3); // TODO сделать листалку

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = (new \EnterRepository\Product\Catalog\Config())->getLimitByHttpRequest($request);

        // сортировка
        $sorting = (new Repository\Product\Sorting())->getObjectByHttpRequest($request);

        // список сортировок
        $sortings = (new Repository\Product\Sorting())->getObjectList();
        // выбранная сортировка
        if (!$sorting) {
            $sorting = reset($sortings);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        // запрос дерева категорий для меню
        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        // запрос товаров по баркодам
        $productListQuery = new Query\Product\GetListByBarcodeList($barcodes, $region->id);
        $curl->prepare($productListQuery);

        $curl->execute();

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

        // товары
        $products = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductSet\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = count($products);
        $pageRequest->requestFilters = [];
        $pageRequest->baseRequestFilters = [];
        $pageRequest->filters = [];
        $pageRequest->sorting = $sorting;
        $pageRequest->sortings = $sortings;
        $pageRequest->category = null;
        $pageRequest->catalogConfig = null;
        $pageRequest->products = $products;

        // страница
        $page = new Page();
        (new Repository\Page\ProductSet\Index())->buildObjectByRequest($page, $pageRequest);

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