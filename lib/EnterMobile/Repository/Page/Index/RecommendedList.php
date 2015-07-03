<?php

namespace EnterMobile\Repository\Page\Index;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Index\RecommendedList as Page;
use EnterAggregator\TemplateHelperTrait;

class RecommendedList {
    use ConfigTrait, LoggerTrait, RouterTrait, DateHelperTrait, TranslateHelperTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param RecommendedList\Request $request
     */
    public function buildObjectByRequest(Page $page, RecommendedList\Request $request) {
        $router = $this->getRouter();

        $url = $router->getUrlByRoute(new Routing\Index\Recommendations());

        // популярные товары
        $popularSlider = $this->buildWidget([
            'sliderName'    => 'popularSlider',
            'sliderUrl'     => $url,
            'sliderItems'   => $request->popularItems,
            'productGA'     => [
                                'category' => 'RR_взаимодействие_мобильный',
                                'events' => [
                                    'addToCart' => [
                                        'action' => 'Добавил в корзину',
                                        'productName' => '',
                                        'label' => 'MainPopular'
                                    ],
                                    'toProductPage' => [
                                        'action' => 'Перешел на карточку товара',
                                        'label' => 'MainPopular',
                                        'product' => ''
                                    ]
                                 ]
                                ],
            'sliderGA'      => [
                                    [
                                        'send',
                                        'event',
                                        'RR_взаимодействие_мобильный',
                                        'Пролистывание',
                                        'MainPopular'
                                    ]
                                ]
        ]);
        $page->widgets['.' . $popularSlider->widgetId] = $popularSlider;
        // персональные рекомендации
        $personalSlider = $this->buildWidget([
            'sliderName' => 'personalSlider',
            'sliderUrl' => $url,
            'sliderItems' => $request->personalItems,
            'productGA'     => [
                                'category' => 'RR_взаимодействие_мобильный',
                                'events' => [
                                    'addToCart' => [
                                        'action' => 'Добавил в корзину',
                                        'productName' => '',
                                        'label' => 'MainRecommended'
                                    ],
                                    'toProductPage' => [
                                        'action' => 'Перешел на карточку товара',
                                        'label' => 'MainRecommended',
                                        'product' => ''
                                    ]
                                ]
                                ],
            'sliderGA'      => [
                                [
                                    'send',
                                    'event',
                                    'RR_взаимодействие_мобильный',
                                    'Пролистывание',
                                    'MainRecommended'
                                ]
                                ]
        ]);
        $page->widgets['.' . $personalSlider->widgetId] = $personalSlider;
        // просмотренное
        $viewedSlider = $this->buildWidget([
            'sliderName' => 'viewedSlider',
            'sliderUrl' => $url,
            'sliderItems' => $request->viewedItems,
            'productGA'     => [
                                'category' => 'RR_взаимодействие_мобильный',
                                'events' => [
                                    'addToCart' => [
                                        'action' => 'Добавил в корзину',
                                        'productName' => '',
                                        'label' => 'Viewed'
                                    ],
                                    'toProductPage' => [
                                        'action' => 'Перешел на карточку товара',
                                        'label' => 'Viewed',
                                        'product' => ''
                                    ]
                                ]
                                ],
            'sliderGA'      => [
                                [
                                    'send',
                                    'event',
                                    'RR_взаимодействие_мобильный',
                                    'Пролистывание',
                                    'Viewed'
                                ]
                                ]
        ]);
        $page->widgets['.' . $viewedSlider->widgetId] = $viewedSlider;
    }

    private function buildWidget($options) {
        $productSliderRepository = new Repository\Partial\ProductSlider();
        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();
        $templateHelper = $this->getTemplateHelper();

        $slider = $productSliderRepository->getObject($options['sliderName'], $options['sliderUrl'], $templateHelper->json($options['sliderGA']));
        $slider->hasCategories = false;

        foreach ($options['sliderItems'] as $productModel) {
            $productModel->sender = [
                'sender[name]'      => 'retailrocket',
                'sender[position]'  => 'ProductAccessories',
                'sender[type]'      => 'alsoBought',
                'sender[method]'    => 'CrossSellItemToItems'
            ];

            $options['productGA']['events']['addToCart']['productName'] = $productModel->name.'(RR_мобильный_'. $options['productGA']['events']['addToCart']['label'] .')';
            $options['productGA']['events']['toProductPage']['productName'] = $productModel->article;

            $productModel->ga = $options['productGA'];

            $productCard = $productCardRepository->getObject(
                $productModel,
                $cartProductButtonRepository->getObject($productModel),
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

        return $slider;
    }
}