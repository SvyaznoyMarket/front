<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCatalog\RootCategory as Page;

class RootCategory {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productCategoryRepository = new \EnterRepository\Product\Category();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен категории
        $categoryToken = $productCategoryRepository->getTokenByHttpRequest($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        
        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        // наличие категорий в данном регионе
        $categoryListQuery = new Query\Product\Category\GetAvailableList(['token' => $categoryToken], $region->id, 1);
        $curl->prepare($categoryListQuery);

        // запрос дерева категорий для меню
        $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
        $curl->prepare($categoryTreeQuery);

        // подробный запрос категории (seo, настройки сортировки, ...)
        /*
        $categoryItemQuery = new Query\Product\Category\GetItemByToken($categoryToken, $region->id);
        $curl->prepare($categoryItemQuery);
        */

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $curl->execute();
        
        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $availableDataByUi = null;
        try {
            if ($categoryListQuery) {
                foreach ($categoryListQuery->getResult() as $item) {
                    $item += ['id' => null, 'uid' => null, 'product_count' => null];

                    if (!$item['uid'] || !$item['product_count']) continue;

                    $availableDataByUi[$item['uid']] = $item;
                }
            }
        } catch (\Exception $e) {
            trigger_error($e, E_USER_ERROR);
        }

        // категория из вехнего списка категорий для меню
        $category = null;
        foreach ($categoryTreeQuery->getResult() as $categoryItem) {
            if ($categoryToken === @$categoryItem['slug']) {
                //$category = new \EnterModel\Product\Category(array_merge_recursive($categoryItemQuery->getResult(), $categoryItem));
                $category = new \EnterModel\Product\Category($categoryItem);

                if (null !== $availableDataByUi) {
                    $category->children = array_filter($category->children, function(\EnterModel\Product\Category $category) use (&$availableDataByUi) { return isset($availableDataByUi[$category->ui]); });
                }
            }
        }
        if (!$category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\RootCategory\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;
        $pageRequest->category = $category;
        $pageRequest->httpRequest = $request;

        // страница
        $page = new Page();
        (new Repository\Page\ProductCatalog\RootCategory())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/product-catalog/root-category/content',
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}