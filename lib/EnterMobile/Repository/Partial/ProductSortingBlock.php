<?php

namespace EnterMobile\Repository\Partial;

use Enter\Http;
use EnterAggregator\RouterTrait;
use EnterAggregator\UrlHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class ProductSortingBlock {
    use RouterTrait, UrlHelperTrait, TemplateHelperTrait;

    /**
     * @param \EnterModel\Product\Sorting[] $sortingModels
     * @param \EnterModel\Product\Sorting|null $currentSortingModel
     * @param Routing\Route|null $route
     * @param Http\Request|null $httpRequest
     * @return \EnterMobile\Model\Partial\SortingBlock
     */
    public function getObject(
        array $sortingModels,
        \EnterModel\Product\Sorting $currentSortingModel = null,
        Routing\Route $route = null,
        Http\Request $httpRequest = null
    ) {
        $router = $this->getRouter();
        $urlHelper = $this->getUrlHelper();
        $viewHelper = $this->getTemplateHelper();

        $block = new Partial\SortingBlock();
        $block->widgetId = 'id-productSorting';

        foreach ($sortingModels as $sortingModel) {
            $urlParams = [
                'sort' => ('default' == $sortingModel->token) ? null : ($sortingModel->token . '-' . $sortingModel->direction),
            ];

            $sorting = new Partial\SortingBlock\Sorting();
            $sorting->name = $sortingModel->name;
            $sorting->dataValue = $viewHelper->json($urlParams);
            if ($route && $httpRequest) {
                $sorting->url = $router->getUrlByRoute($route, $urlHelper->replace($route, $httpRequest, $urlParams));
            }

            if ($currentSortingModel && ($currentSortingModel->token == $sortingModel->token) && ($currentSortingModel->direction == $sortingModel->direction)) {
                $block->sorting = $sorting;
                $sorting->isActive = true;
            } else {
                $sorting->isActive = false;
            }

            // ga
            $sorting->dataGa = $viewHelper->json([
                'm_sort_button' => ['send', 'event', 'm_sort_button', $sorting->name],
            ]);

            $block->sortings[] = $sorting;
        }

        return $block;
    }
}