<?php

namespace EnterMobile\Repository\Page\ProductSet;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCatalog\ChildCategory as Page;

class Index {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $productCardRepository = new Repository\Partial\ProductCard();
        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();

        $page->dataModule = 'product.catalog';

        $currentRoute = new Routing\ProductSet\Index(implode(',', array_map(function(\EnterModel\Product $product){ return $product->barcode; }, $request->products)));

        $page->content->categoryBlock = false;

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

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
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
            $page->mailRu->pageType = 'product_set';
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}