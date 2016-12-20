<?php

namespace EnterMobile\Controller\ProductSet;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;
use EnterQuery;
use EnterAggregator\AbTestTrait;

class Index {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait, AbTestTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

        $productRepository = new \EnterRepository\Product();

        $barcodes = explode(',', is_scalar($request->query['productBarcodes']) ? $request->query['productBarcodes'] : '');
        $barcodes = array_filter($barcodes, function($barcode) { $barcode = trim($barcode); return !empty($barcode); });
        if (!(bool)$barcodes) {
            return (new Controller\Error\NotFound())->execute($request, 'Не переданы баркоды товаров');
        }

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // номер страницы
        $pageNum = (new Repository\PageNum())->getByHttpRequest($request);
        $limit = $productRepository->getLimitByHttpRequest($request);

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

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        
        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        // запрос дерева категорий для меню
        $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
        $curl->prepare($categoryTreeQuery);

        // запрос товаров по баркодам
        $productListQuery = new Query\Product\GetListByBarcodeList($barcodes, $region->id);
        $productDescriptionListQuery = new Query\Product\GetDescriptionListByBarcodeList($barcodes, [
            'media'       => true,
            'media_types' => ['main'], // только главная картинка
            'category'    => true,
            'label'       => true,
            'brand'       => true,
        ]);
        $curl->prepare($productListQuery);
        $curl->prepare($productDescriptionListQuery);

        $curl->execute();
        
        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);

        // товары
        $productsById = $productRepository->getIndexedObjectListByQueryList([$productListQuery], [$productDescriptionListQuery]);

        $productsById = array_filter($productsById, function(\EnterModel\Product $product) {
            return ($product->isBuyable || $product->isInShopShowroomOnly) && $product->statusId != 5;
        });


        if ($config->productReview->enabled) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductIdList(array_map(function(\EnterModel\Product $product) { return $product->id; }, $productsById));
            $curl->prepare($ratingListQuery);
            $curl->execute();
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        usort($productsById, function (\EnterModel\Product $productX, \EnterModel\Product $productY) {
            $a = $productX->isBuyable;
            $b = $productY->isBuyable;

            if ($a != $b) {
                return (int)$a < (int)$b ? -1 : 1;
            }

            $a = $productX->rating ? $productX->rating->score : 0;
            $b = $productY->rating ? $productY->rating->score : 0;

            if ($a == $b) {
                return 0;
            }

            return (int)$a < (int)$b ? -1 : 1;
        });

        $productsById = array_reverse($productsById, true);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductSet\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;
        $pageRequest->pageNum = $pageNum;
        $pageRequest->limit = $limit;
        $pageRequest->count = count($productsById);
        $pageRequest->requestFilters = [];
        $pageRequest->baseRequestFilters = [];
        $pageRequest->filters = [];
        $pageRequest->sorting = $sorting;
        $pageRequest->sortings = $sortings;
        $pageRequest->category = null;
        $pageRequest->catalogConfig = null;
        $pageRequest->products = $productsById;

        // AB тест
        $chosenListingType = $this->getAbTest()->getObjectByToken('product_listing')->chosenItem->token;

        if ($chosenListingType == 'old_listing') {
            $pageRequest->buyBtnListing = false;
        } else if ($chosenListingType == 'new_listing') {
            $pageRequest->buyBtnListing = true;
        }

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