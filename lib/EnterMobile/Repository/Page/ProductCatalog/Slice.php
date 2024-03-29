<?php

namespace EnterMobile\Repository\Page\ProductCatalog;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\TemplateRepositoryTrait;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;

class Slice {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param Slice\Request $request
     */
    public function buildObjectByRequest(Page $page, Slice\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();

        // заголовок
        $page->title = $request->slice->name . ' - Enter';

        $page->dataModule = 'product.catalog';

        // хлебные крошки
        $categories = call_user_func(function() use (&$request) {
            if (!$request->category) {
                return [];
            }

            $ancestors = [];
            $parent = $request->category->parent;
            while ($parent) {
                $ancestors[] = $parent;

                $parent = $parent->parent;
            }

            return array_reverse($ancestors);
        });
        $page->breadcrumbBlock = new Model\Page\DefaultPage\BreadcrumbBlock();
        $breadcrumb = new Model\Page\DefaultPage\BreadcrumbBlock\Breadcrumb();
        $breadcrumb->name = $request->slice->name;
        $breadcrumb->url = $router->getUrlByRoute(new Routing\ProductSlice\Get($request->slice->token));
        $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;
        foreach ($categories as $categoryModel) {
            $breadcrumb = new Model\Page\DefaultPage\BreadcrumbBlock\Breadcrumb();
            $breadcrumb->name = $categoryModel->name;
            $breadcrumb->url = $router->getUrlByRoute(new Routing\ProductSlice\GetCategory($request->slice->token, $categoryModel->token));
            $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;
        }

        $currentRoute = new Routing\ProductSlice\Get($request->slice->token);

        $page->content->categoryBlock = false;
        if ($request->categories) {
            $page->content->categoryBlock = new Partial\ProductCatalog\CategoryBlock();
            foreach ($request->categories as $iCategoryModel) {
                $childCategory = new Partial\ProductCatalog\CategoryBlock\Category();
                $childCategory->name = $iCategoryModel->name;
                $childCategory->url = $router->getUrlByRoute(new Routing\ProductSlice\GetCategory($request->slice->token, $iCategoryModel->token));
                $childCategory->image = (string)(new Routing\Product\Category\GetImage($iCategoryModel, 'category_163x163'));

                $page->content->categoryBlock->categories[] = $childCategory;
            }
        }

        // TODO: вынести productBlock в репозиторий
        $page->content->productBlock = new Partial\ProductBlock();
        $page->content->productBlock->limit = $config->product->itemPerPage;
        $page->content->productBlock->url = $router->getUrlByRoute(new Routing\Product\GetListByFilter());
        // [data-reset] && [data-value]
        $dataReset = [
            'page'       => 1,
            'limit'      => $page->content->productBlock->limit,
            'count'      => $request->count,
            'sort'       => ('default' == $request->sorting->token) ? null : ($request->sorting->token . '-' . $request->sorting->direction),
        ];

        $dataValue = $dataReset;
        $dataValue['page']++;
        foreach ($request->baseRequestFilters as $requestFilter) {
            if ('category' == $requestFilter->name) { // FIXME
                $dataReset[$requestFilter->name][] = $requestFilter->value;
            } else {
                $dataReset[$requestFilter->name] = $requestFilter->value;
            }
        }
        foreach (array_merge($request->baseRequestFilters, $request->requestFilters) as $requestFilter) {
            if (!$requestFilter->name) {
                $this->getLogger()->push(['type' => 'warn', 'message' => 'Пустой токен', 'requestFilter' => $requestFilter, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
                continue;
            }

            if (('category' == $requestFilter->name) && !$request->category) { // FIXME
                $dataValue[$requestFilter->name][] = $requestFilter->value;
            } else {
                $dataValue[$requestFilter->name] = $requestFilter->value;
            }
        }
        $page->content->productBlock->dataValue = $templateHelper->json($dataValue);
        $page->content->productBlock->dataReset = $templateHelper->json($dataReset);

        foreach ($request->products as $productModel) {
            $productCard = $productCardRepository->getObject($productModel, $cartProductButtonRepository->getObject($productModel));

            $page->content->productBlock->products[] = $productCard;
        }

        $page->content->sortingBlock = (new Repository\Partial\ProductSortingBlock())->getObject(
            $request->sortings,
            $request->sorting,
            $currentRoute,
            $request->httpRequest
        );

        $page->content->productBlock->moreLink = (new Repository\Partial\ProductList\MoreLink())->getObject($request->pageNum, $request->limit, $request->count, $request->category) ?: false;

        // фильтры
        $page->content->filterBlock = false;
        if ((bool)($filters = (new Repository\Partial\ProductFilter())->getList($request->filters, $request->requestFilters, false))) {
            $page->content->filterBlock = new Partial\ProductFilterBlock();
            $page->content->filterBlock->filters = $filters;
            $page->content->filterBlock->openedFilters = (new Repository\Partial\ProductFilter())->getList($request->filters, $request->requestFilters, true);
            $page->content->filterBlock->actionBlock->shownProductCount = sprintf('Показать (%s)', $request->count > 999 ? '&infin;' : $request->count);

            if ($request->category) {
                $page->content->filterBlock->dataGa = $templateHelper->json([
                    'm_cat_params' => ['send', 'event', 'm_cat_params', $request->category->name],
                ]);
            }
        }

        // выбранные фильтры
        $page->content->selectedFilterBlock = new Partial\SelectedFilterBlock();
        $page->content->selectedFilterBlock->filters = (new Repository\Partial\ProductFilter())->getSelectedList(
            $request->filters,
            $request->requestFilters,
            $currentRoute,
            $request->httpRequest
        );
        $page->content->selectedFilterBlock->hasFilter = (bool)$page->content->selectedFilterBlock->filters;

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForProductCatalog($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // AB test
        $page->buyBtnListing = $request->buyBtnListing;

        // шаблоны mustache
        $this->getTemplateRepository()->setListForPage($page, [
            [
                'id'   => 'tpl-productList-moreLink',
                'name' => 'partial/product-list/moreLink',
            ],
            [
                'id'   => 'tpl-product-selectedFilter',
                'name' => 'partial/product-list/selectedFilter',
            ],
            [
                'id'   => 'tpl-productSorting',
                'name' => 'partial/product-list/sorting',
            ],
            [
                'id'   => 'tpl-productFilter-action',
                'name' => 'partial/product-list/filterAction',
            ],
            [
                'id'   => 'tpl-productList-noProducts',
                'name' => 'partial/product-list/noProducts',
            ],
        ]);

        if (is_object($page->mailRu)) {
            $page->mailRu->pageType = 'slice';
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}