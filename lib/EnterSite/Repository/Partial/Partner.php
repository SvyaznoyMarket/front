<?php

namespace EnterSite\Repository\Partial;

use EnterSite\ConfigTrait;
use EnterSite\ViewHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;

class Partner {
    use ConfigTrait, ViewHelperTrait;

    /**
     * Данные по умолчанию
     *
     * @param Repository\Page\DefaultLayout\Request $request
     * @return array
     */
    public function getDefaultList(Repository\Page\DefaultLayout\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $dataAction = 'default';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для главной страницы
     *
     * @param Repository\Page\Index\Request $request
     * @return array
     */
    public function getListForIndex(Repository\Page\Index\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $dataAction = 'index';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json([
                'pageType' => 1,
            ]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для каталога товаров
     *
     * @param Repository\Page\ProductCatalog\RootCategory\Request $request
     * @return array
     */
    public function getListForProductCatalog(Repository\Page\ProductCatalog\RootCategory\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $category = $request->category;
        $products = $request instanceof Repository\Page\ProductCatalog\ChildCategory\Request ? $request->products : [];
        $dataAction = 'product.catalog';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json([
                'pageType'         => 3,
                'currentCategory'  => ['id' => $category->id, 'name' => $category->name],
                'parentCategories' => $category->parent
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name],
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            if ((bool)$products) {

                $partner = new Partial\Partner();
                $partner->id = 'criteo';
                $partner->dataAction = $dataAction;
                $partner->dataValue = $viewHelper->json(array_merge($this->getCriteoDataValue(), [
                    [
                        'event' => 'viewList',
                        'item'  => array_values(array_map(function(\EnterModel\Product $product) { return $product->id; }, $products)),
                    ],
                ]));

                $partners[] = $partner;
            }
        }

        return $partners;
    }

    /**
     * Данные для результатов поиска
     *
     * @param Repository\Page\Search\Request $request
     * @return array
     */
    public function getListForSearch(Repository\Page\Search\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $products = $request->products;
        $dataAction = 'search';

        $partners = [];

        // criteo
        if ($config->criteo->enabled) {
            if ((bool)$products) {

                $partner = new Partial\Partner();
                $partner->id = 'criteo';
                $partner->dataAction = $dataAction;
                $partner->dataValue = $viewHelper->json(array_merge($this->getCriteoDataValue(), [
                    [
                        'event' => 'viewList',
                        'item'  => array_values(array_map(function(\EnterModel\Product $product) { return $product->id; }, $products)),
                    ],
                ]));

                $partners[] = $partner;
            }
        }

        return $partners;
    }

    /**
     * Данные для карточки товара
     *
     * @param Repository\Page\ProductCard\Request $request
     * @return array
     */
    public function getListForProductCard(Repository\Page\ProductCard\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $product = $request->product;
        $category = $request->product->category;
        $dataAction = 'product.card';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json([
                'pageType'         => 2,
                'currentProduct'   => ['id' => $product->id, 'name' => $product->name, 'price' => $product->price],
                'currentCategory'  => $category ? ['id' => $category->id, 'name' => $category->name] : null,
                'parentCategories' => ($category && $category->parent)
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name],
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'criteo';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json(array_merge($this->getCriteoDataValue(), [
                [
                    'event'   => 'viewItem',
                    'account' => $product->id,
                ],
            ]));

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для корзины
     *
     * @param Repository\Page\Cart\Index\Request $request
     * @return array
     */
    public function getListForCart(Repository\Page\Cart\Index\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $cart = $request->cart;
        $dataAction = 'cart';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json([
                'pageType' => 4,
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'criteo';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $viewHelper->json(array_merge($this->getCriteoDataValue(), [
                [
                    'event' => 'viewBasket',
                    'item'  => array_values(array_map(function(\EnterModel\Cart\Product $product) { return [
                        'id'       => $product->id,
                        'price'    => $product->price,
                        'quantity' => $product->quantity,
                    ]; }, $cart->product)),
                ],
            ]));

            $partners[] = $partner;
        }

        return $partners;
    }


    /**
     * @return array
     */
    private function getCriteoDataValue() {
        $config = $this->getConfig()->partner->service->criteo;

        return [
            [
                'event'   => 'setAccount',
                'account' => $config->account,
            ],
            [
                'event' => 'setCustomerId',
                'id'    => '{userId}',
            ],
            [
                'event' => 'setSiteType',
                'type'  => 'm',
            ],
        ];
    }
}