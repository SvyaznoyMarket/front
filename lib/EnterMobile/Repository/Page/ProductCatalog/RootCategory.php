<?php

namespace EnterMobile\Repository\Page\ProductCatalog;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCatalog\RootCategory as Page;

class RootCategory {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Page $page
     * @param RootCategory\Request $request
     */
    public function buildObjectByRequest(Page $page, RootCategory\Request $request) {
        (new Repository\Page\DefaultLayout)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();

        $page->dataModule = 'product.catalog';

        $page->content->title = $request->category->name;

        // хлебные крошки
        $page->breadcrumbBlock = new Model\Page\DefaultLayout\BreadcrumbBlock();
        $breadcrumb = new Model\Page\DefaultLayout\BreadcrumbBlock\Breadcrumb();
        $breadcrumb->name = $request->category->name;
        $breadcrumb->url = $request->category->link;
        $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;

        $page->content->categoryBlock = false;
        if ((bool)$request->category->children) {
            $page->content->categoryBlock = new Partial\ProductCatalog\CategoryBlock();
            foreach ($request->category->children as $childCategoryModel) {
                $childCategory = new Partial\ProductCatalog\CategoryBlock\Category();
                $childCategory->name = $childCategoryModel->name;
                $childCategory->url = $childCategoryModel->link;
                $childCategory->image = (string)(new Routing\Product\Category\GetImage($childCategoryModel->image, $childCategoryModel->id, 1));

                $page->content->categoryBlock->categories[] = $childCategory;
            }
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForProductCatalog($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['partner']]);
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}