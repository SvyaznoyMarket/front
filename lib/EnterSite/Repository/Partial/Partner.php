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
            $dataValue = [
                'pageType'         => 3,
                'currentCategory'  => ['id' => $category->id, 'name' => $category->name],
                'parentCategories' => $category->parent
                    ? [ ['id' => $category->parent->id, 'name' => $category->parent->name] ]
                    : [],
            ];
            $partner->dataValue = $viewHelper->json($dataValue);

            $partners[] = $partner;
        }

        return $partners;
    }
}