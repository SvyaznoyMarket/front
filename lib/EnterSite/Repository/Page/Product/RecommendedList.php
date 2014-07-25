<?php

namespace EnterSite\Repository\Page\Product;

use EnterSite\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;
use EnterSite\Model\Page\Product\RecommendedList as Page;
use EnterAggregator\TemplateHelperTrait;

class RecommendedList {
    use ConfigTrait, LoggerTrait, RouterTrait, DateHelperTrait, TranslateHelperTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param RecommendedList\Request $request
     */
    public function buildObjectByRequest(Page $page, RecommendedList\Request $request) {
        $router = $this->getRouter();
        $viewHelper = $this->getTemplateHelper();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();
        $productCardRepository = new Repository\Partial\ProductCard();
        $productSliderRepository = new Repository\Partial\ProductSlider();

        $url = $router->getUrlByRoute(new Routing\Product\GetRecommendedList($request->product->id));

        // alsoBought slider
        $slider = $productSliderRepository->getObject('alsoBoughtSlider', $url);
        $slider->hasCategories = false;
        foreach ($request->alsoBoughtIdList as $productId) {
            /** @var \EnterModel\Product|null $productModel */
            $productModel = !empty($request->productsById[$productId]) ? $request->productsById[$productId] : null;
            if (!$productModel) continue;

            $productCard = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel));
            $productCard->dataGa = $viewHelper->json([
                'm_recommended' => ['send', 'event', 'm_recommended', $productModel->article],
            ]);

            $slider->productCards[] = $productCard;
        }
        $slider->count = count($slider->productCards);
        $page->widgets['.' . $slider->widgetId] = $slider;

        // alsoViewed slider
        $slider = $productSliderRepository->getObject('alsoViewedSlider', $url);
        $slider->hasCategories = false;
        foreach ($request->alsoViewedIdList as $productId) {
            /** @var \EnterModel\Product|null $productModel */
            $productModel = !empty($request->productsById[$productId]) ? $request->productsById[$productId] : null;
            if (!$productModel) continue;

            $slider->productCards[] = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel));
        }
        $slider->count = count($slider->productCards);
        $page->widgets['.' . $slider->widgetId] = $slider;

        // similar slider
        $slider = $productSliderRepository->getObject('similarSlider', $url);;
        $slider->hasCategories = false;
        foreach ($request->similarIdList as $productId) {
            /** @var \EnterModel\Product|null $productModel */
            $productModel = !empty($request->productsById[$productId]) ? $request->productsById[$productId] : null;
            if (!$productModel) continue;

            $slider->productCards[] = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel));
        }
        $slider->count = count($slider->productCards);
        $page->widgets['.' . $slider->widgetId] = $slider;
    }
}