<?php

namespace EnterMobile\Repository\Page\ProductCatalog;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;

class ChildCategory {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param ChildCategory\Request $request
     */
    public function buildObjectByRequest(Page $page, ChildCategory\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $viewHelper = $this->getTemplateHelper();

        $templateDir = $config->mustacheRenderer->templateDir;

        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();

        // заголовок
        $page->title = $request->category->name . ' - Enter';

        $page->dataModule = 'product.catalog';

        $page->content->title = $request->category->name;

        // хлебные крошки
        $page->breadcrumbBlock = new Model\Page\DefaultPage\BreadcrumbBlock();
        $breadcrumb = new Model\Page\DefaultPage\BreadcrumbBlock\Breadcrumb();
        $breadcrumb->name = $request->category->name;
        $breadcrumb->url = $request->category->link;
        $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;

        $currentRoute = new Routing\ProductCatalog\GetChildCategory($request->category->path);

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

        $page->content->productBlock = false;
        $page->content->sortingBlock = false;
        if ((bool)$request->products) {
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
                $dataReset[$requestFilter->name] = $requestFilter->value;
            }
            foreach (array_merge($request->baseRequestFilters, $request->requestFilters) as $requestFilter) {
                if (!$requestFilter->name) {
                    $this->getLogger()->push(['type' => 'warn', 'message' => 'Пустой токен', 'requestFilter' => $requestFilter, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
                    continue;
                }
                $dataValue[$requestFilter->name] = $requestFilter->value;
            }
            $page->content->productBlock->dataValue = $viewHelper->json($dataValue);
            $page->content->productBlock->dataReset = $viewHelper->json($dataReset);

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
                $page->content->filterBlock->dataGa = $viewHelper->json([
                    'm_cat_params' => ['send', 'event', 'm_cat_params', $request->category->name],
                ]);
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
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForProductCatalog($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        foreach ([
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
        ] as $templateItem) {
            try {
                $template = new Model\Page\DefaultPage\Template();
                $template->id = $templateItem['id'];
                $template->content = file_get_contents($templateDir . '/' . $templateItem['name'] . '.mustache');

                $page->templates[] = $template;
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['template']]);
            }
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}