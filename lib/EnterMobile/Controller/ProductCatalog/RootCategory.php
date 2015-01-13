<?php

namespace EnterMobile\Controller\ProductCatalog;

use Enter\Http;
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
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $productCategoryRepository = new \EnterRepository\Product\Category();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен категории
        $categoryToken = $productCategoryRepository->getTokenByHttpRequest($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // запрос категории
        $categoryItemQuery = new Query\Product\Category\GetItemByToken($categoryToken, $region->id);
        $curl->prepare($categoryItemQuery);

        $curl->execute();

        // категория
        $category = $productCategoryRepository->getObjectByQuery($categoryItemQuery);

        if (!$category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара @%s не найдена', $categoryToken));
        }

        // запрос предка категории
        $branchCategoryItemQuery = new Query\Product\Category\GetBranchItemByCategoryObject($category, $region->id);
        $curl->prepare($branchCategoryItemQuery);

        // запрос дерева категорий для меню
        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        $curl->execute();

        // предки и дети категории
        $productCategoryRepository->setBranchForObjectByQuery($category, $branchCategoryItemQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        // запрос настроек каталога
        $catalogConfigQuery = new Query\Product\Catalog\Config\GetItemByProductCategoryUi($category->ui, $regionId);
        $curl->prepare($catalogConfigQuery);

        $curl->execute();

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

        // настройки каталога
        $catalogConfig = (new \EnterRepository\Product\Catalog\Config())->getObjectByQuery($catalogConfigQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCatalog\RootCategory\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->category = $category;
        $pageRequest->catalogConfig = $catalogConfig;

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