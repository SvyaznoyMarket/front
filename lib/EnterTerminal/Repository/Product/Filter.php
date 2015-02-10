<?php

namespace EnterTerminal\Repository\Product;

use Enter\Http;
use EnterMobile\Repository\Product\Filter as BaseRepository;
use EnterModel as Model;

class Filter extends BaseRepository {
    /**
     * @param Http\Request $request
     * @return Model\Product\RequestFilter[]
     */
    public function getRequestObjectListByHttpRequest(Http\Request $request) {
        $filters = [];

        foreach ((array)$request->query['filter'] as $key => $value) {
            foreach ((array)$value as $optionToken => $optionValue) {
                $filter = new Model\Product\RequestFilter();
                $filter->token = $key;
                $filter->name = $key;
                $filter->value = $optionValue;

                $filter->optionToken = $optionToken;

                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @param Model\Product\RequestFilter[] $requestFilters
     * @param string $filterToken
     * @return Model\Product\RequestFilter|null
     */
    public function getObjectByToken(array $requestFilters, $filterToken) {
        foreach ($requestFilters as $requestFilter) {
            if ($requestFilter->token === $filterToken) {
                return $requestFilter;
            }
        }

        return null;
    }

    /**
     * @param Model\Product\RequestFilter[] $requestFilters
     * @return array
     */
    public function dumpRequestObjectList(array $requestFilters) {
        $return = [];

        $filterData = [];
        foreach ($requestFilters as $requestFilter) {
            $key = $requestFilter->token;
            $value = $requestFilter->value;

            if (0 === strpos($key, 'tag-')) {
                $key = 'tag_and';
            }

            if (!isset($filterData[$key])) {
                $filterData[$key] = [
                    'value' => [],
                ];
            }

            if (('from' === $requestFilter->optionToken) || ('to' === $requestFilter->optionToken)) {
                $filterData[$key]['value'][$requestFilter->optionToken] = $value;
            } else {
                $filterData[$key]['value'][] = $value;
            }
        }

        foreach ($filterData as $key => $filter) {
            if ('segment' === $key) {
                $return[] = ['segment', 4, $filter['value']];
            } else if (isset($filter['value']['from']) || isset($filter['value']['to'])) {
                $return[] = [$key, 2, isset($filter['value']['from']) ? $filter['value']['from'] : null, isset($filter['value']['to']) ? $filter['value']['to'] : null];
            } else {
                $return[] = [$key, 1, $filter['value']];
            }
        }

        return $return;
    }
}