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

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = 'default';

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
    public function getIndexList(Repository\Page\Index\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = 'index';
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
    public function getProductCatalogList(Repository\Page\ProductCatalog\RootCategory\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $category = $request->category;

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = 'product.catalog';
            $partner->dataValue = $viewHelper->json([
                'pageType'         => 3,
                'currentCategory'  => ['id' => $category->id, 'name' => $category->name],
                'parentCategories' => $category->parent
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name]
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для каталога товаров
     *
     * @param Repository\Page\ProductCard\Request $request
     * @return array
     */
    public function getProductCardList(Repository\Page\ProductCard\Request $request) {
        $config = $this->getConfig()->partner->service;
        $viewHelper = $this->getViewHelper();

        $product = $request->product;
        $category = $request->product->category;

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = 'product.card';
            $partner->dataValue = $viewHelper->json([
                'pageType'         => 2,
                'currentProduct'   => ['id'    => $product->id, 'name'  => $product->name, 'price' => $product->price],
                'currentCategory'  => $category ? ['id' => $category->id, 'name' => $category->name] : null,
                'parentCategories' => ($category && $category->parent)
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name]
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        return $partners;
    }
}