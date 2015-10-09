<?php

namespace EnterMobile\Repository\Page\Product;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Product\RecommendedList as Page;
use EnterAggregator\TemplateHelperTrait;

class RecommendedList {
    use ConfigTrait, LoggerTrait, RouterTrait, DateHelperTrait, TranslateHelperTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param RecommendedList\Request $request
     */
    public function buildObjectByRequest(Page $page, RecommendedList\Request $request) {
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();
        $productCardRepository = new Repository\Partial\ProductCard();
        $productSliderRepository = new Repository\Partial\ProductSlider();

        $url = $router->getUrlByRoute(new Routing\Product\GetRecommendedList($request->product->id));

        // alsoBought slider
        $sliderGa = $templateHelper->json([
            [
                'send',
                'event',
                'RR_взаимодействие_мобильный',
                'Пролистывание',
                'ProductAccessories'
            ]
        ]);
        $slider = $productSliderRepository->getObject('alsoBoughtSlider', $url, $sliderGa);
        $slider->hasCategories = false;
        foreach ($request->alsoBoughtIdList as $productId) {
            /** @var \EnterModel\Product|null $productModel */
            $productModel = !empty($request->recommendedProductsById[$productId]) ? $request->recommendedProductsById[$productId] : null;
            if (!$productModel) continue;

            $productModel->sender = [
                'sender[name]'      => 'retailrocket',
                'sender[position]'  => 'ProductAccessories',
                'sender[type]'      => 'alsoBought',
                'sender[method]'    => 'CrossSellItemToItems'
            ];

            $productModel->ga = [
                'category' => 'RR_взаимодействие_мобильный',
                'events' => [
                    'addToCart' => [
                        'action' => 'Добавил в корзину',
                        'productName' => $productModel->name.'(RR_мобильный_ProductAccessories)',
                        'label' => 'ProductAccessories'
                    ],
                    'toProductPage' => [
                        'action' => 'Перешел на карточку товара',
                        'label' => 'ProductAccessories',
                        'product' => $productModel->article
                    ]
                ]
            ];

            $productCard = $productCardRepository->getObject(
                $productModel,
                $cartProductButtonRepository->getObject($productModel, null, true, true, ['position' => 'listing']),
                null,
                'product_60'
            );
            $productCard->dataGa = $templateHelper->json([
                [
                    'send',
                    'event',
                    $productModel->ga['category'],
                    $productModel->ga['events']['toProductPage']['action'],
                    $productModel->ga['events']['toProductPage']['product']
                ]
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
            $productModel = !empty($request->recommendedProductsById[$productId]) ? $request->recommendedProductsById[$productId] : null;
            if (!$productModel) continue;

            $slider->productCards[] = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel));
        }
        $slider->count = count($slider->productCards);
        $page->widgets['.' . $slider->widgetId] = $slider;

        // similar slider
        $sliderGa = $templateHelper->json([
            [
                'send',
                'event',
                'RR_взаимодействие_мобильный',
                'Пролистывание',
                'ProductSimilar'
            ]
        ]);
        $slider = $productSliderRepository->getObject('similarSlider', $url, $sliderGa);;
        $slider->hasCategories = false;
        foreach ($request->similarIdList as $productId) {
            /** @var \EnterModel\Product|null $productModel */
            $productModel = !empty($request->recommendedProductsById[$productId]) ? $request->recommendedProductsById[$productId] : null;
            if (!$productModel) continue;

            $productModel->sender = [
                'sender[name]'      => 'retailrocket',
                'sender[position]'  => 'ProductSimilar',
                'sender[type]'      => 'similar',
                'sender[method]'    => 'UpSellItemToItems'
            ];

            $productModel->ga = [
                'category' => 'RR_взаимодействие_мобильный',
                'events' => [
                    'addToCart' => [
                        'action' => 'Добавил в корзину',
                        'productName' => $productModel->name.'(RR_мобильный_ProductSimilar)',
                        'label' => 'ProductSimilar'
                    ],
                    'toProductPage' => [
                        'action' => 'Перешел на карточку товара',
                        'label' => 'ProductSimilar',
                        'product' => $productModel->article
                    ]
                ]
            ];

            $productCard = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel, null, true, true, ['position' => 'listing']));
            $productCard->dataGa = $templateHelper->json([
                [
                    'send',
                    'event',
                    $productModel->ga['category'],
                    $productModel->ga['events']['toProductPage']['action'],
                    $productModel->ga['events']['toProductPage']['product']
                ]
            ]);
            $slider->productCards[] = $productCard;
        }
        $slider->count = count($slider->productCards);
        $page->widgets['.' . $slider->widgetId] = $slider;
    }
}