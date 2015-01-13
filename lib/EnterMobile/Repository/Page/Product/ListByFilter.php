<?php

namespace EnterMobile\Repository\Page\Product;

use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Product\ListByFilter as Page;

class ListByFilter {
    use ConfigTrait, MustacheRendererTrait;

    /**
     * @param Page $page
     * @param ListByFilter\Request $request
     */
    public function buildObjectByRequest(Page $page, ListByFilter\Request $request) {
        $renderer = $this->getRenderer();

        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();

        $page->count = $request->count;
        $page->page = $request->pageNum;
        $page->limit = $request->limit;

        foreach ($request->products as $productModel) {
            $productCard = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel), $request->category);

            $page->productCards[] = $renderer->render('partial/product-card/default', $productCard);
        }

        // виджеты
        if ($widget = (new Repository\Partial\ProductList\MoreLink())->getObject($request->pageNum, $request->limit, $request->count)) {
            $page->widgets['.' . $widget->widgetId] = $widget;
        }

        // TODO: вынести в репозиторий
        $widget = new Partial\SelectedFilterBlock();
        $widget->filters = (new Repository\Partial\ProductFilter())->getSelectedList(
            $request->filters,
            $request->requestFilters
        );
        $widget->hasFilter = (bool)$widget->filters;
        $page->widgets['.' . $widget->widgetId] = $widget;

        $widget = (new Repository\Partial\ProductSortingBlock())->getObject(
            $request->sortings,
            $request->sorting
        );
        $page->widgets['.' . $widget->widgetId] = $widget;

        $widget = new Partial\ProductFilterActionBlock();
        $widget->shownProductCount = sprintf('Показать (%s)', $request->count > 999 ? '&infin;' : $request->count);
        $page->widgets['.' . $widget->widgetId] = $widget;

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}