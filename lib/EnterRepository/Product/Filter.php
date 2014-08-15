<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Filter {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Query $query
     * @return Model\Product\Filter[]
     */
    public function getObjectListByQuery(Query $query) {
        $filters = [];

        try {
            foreach ($query->getResult() as $item) {
                $filters[] = new Model\Product\Filter($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);

            trigger_error($e, E_USER_ERROR);
        }

        return $filters;
    }

    /**
     * @param Model\Product\Category[] $categories
     * @return Model\Product\Filter[]
     */
    public function getObjectListByCategoryList(array $categories) {
        $filters = [];

        $categoryOptionData = [];
        foreach ($categories as $category) {
            $categoryOptionData[] = [
                'id'       => $category->id,
                'token'    => $category->id,
                'name'     => $category->name,
                'quantity' => $category->productCount,
                'image'    => $category->image,
            ];
        }
        $filters[] = new Model\Product\Filter([
            'filter_id' => 'category',
            'name'      => 'Категории',
            'type_id'   => Model\Product\Filter::TYPE_LIST,
            'options'   => $categoryOptionData,
        ]);

        return $filters;
    }

    /**
     * @param Model\Product\Category $category
     * @return Model\Product\RequestFilter
     */
    public function getRequestObjectByCategory(Model\Product\Category $category) {
        $filter = new Model\Product\RequestFilter();
        $filter->token = 'category';
        $filter->name = 'category';
        $filter->value = $category->id;

        return $filter;
    }

    /**
     * @param Model\Product\RequestFilter[] $requestFilters
     * @return array
     */
    public function dumpRequestObjectList(array $requestFilters) {
        // FIXME

        return [];
    }

    /**
     * @param Model\Product\Filter[] $filters
     * @param Model\Product\RequestFilter[] $requestFilters
     */
    public function setValueForObjectList(array $filters, array $requestFilters) {
        if (!(bool)$requestFilters) {
            return;
        }

        $filtersByToken =[];
        foreach ($filters as $filter) {
            $filtersByToken[$filter->token] = $filter;
        }

        foreach ($requestFilters as $requestFilter) {
            $filter = isset($filtersByToken[$requestFilter->token]) ? $filtersByToken[$requestFilter->token] : null;
            if (!$filter) {
                continue;
            }

            // FIXME
            $filter->isSelected = true;
            if (!isset($filter->value)) {
                $filter->value = [];
            }

            if ($requestFilter->optionToken) {
                $filter->value[$requestFilter->optionToken] = $requestFilter->value;
            } else {
                $filter->value[] = $requestFilter->value;
            }
        }
    }
}