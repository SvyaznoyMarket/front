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
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();

        $page->title = $request->category->name . ' - Enter';

        $page->dataModule = 'product.catalog';

        $page->content->title = $request->category->name;

        $isTchibo = $request->catalogConfig && $request->catalogConfig->tchibo;
        $page->content->categoryBlock = false;
        if ((bool)$request->category->children) {
            $page->content->categoryBlock = new Partial\ProductCatalog\CategoryBlock();
            foreach ($request->category->children as $childCategoryModel) {
                $childCategory = new Partial\ProductCatalog\CategoryBlock\Category();
                $childCategory->name = $childCategoryModel->name;
                $childCategory->url = $childCategoryModel->link;
                $childCategory->image = (string)(new Routing\Product\Category\GetImage($childCategoryModel, $isTchibo ? 'category_1000x1000' : 'category_163x163'));

                $page->content->categoryBlock->categories[] = $childCategory;
            }
        }

        if ($isTchibo && $page->content->categoryBlock) {
            foreach (array_chunk($page->content->categoryBlock->categories, 2) as $i => $categories) {
                $page->content->categoryBlock->categoriesGroupedByRow[] = [
                    'id'         => $i,
                    'categories' => $categories,
                ];
            }
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForProductCatalog($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}