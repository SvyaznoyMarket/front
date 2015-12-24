<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Favorites as Page;


class Favorites {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        PriceHelperTrait,
        DateHelperTrait;

    /**
     * @param Page $page
     * @param Favorites\Request $request
     */
    public function buildObjectByRequest(Page $page, Favorites\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();
        $router = $this->getRouter();
        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();

        $page->title = 'Избранное';

        $page->dataModule = 'user';

        foreach ($request->favoriteProducts as $productModel) {
            $productCard = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel, null, true, true, ['position' => 'private']), null, 'product_160');
            $productCard->deleteUrl = $router->getUrlByRoute(new Routing\Product\DeleteFavorite($productModel->ui));
            $page->content->productCards[] = $productCard;
        }

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [

        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}