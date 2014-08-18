<?php

namespace EnterMobile\Repository\Product;

use Enter\Http;
use EnterRepository\Product\Filter as BaseRepository;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Filter extends BaseRepository {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @return Model\Product\RequestFilter[]
     */
    public function getRequestObjectListByHttpRequest(Http\Request $request) {
        $filters = [];

        foreach ($request->query as $key => $value) {
            if (
                is_scalar($value)
                && (
                    (0 === strpos($key, 'f-'))
                    || (0 === strpos($key, 'tag-'))
                    || (in_array($key, ['shop', 'category', 'slice']))
                )
            ) {
                $filter = new Model\Product\RequestFilter();
                $filter->name = $key;
                $filter->value = $value;

                $keyParts = array_pad(explode('-', $key), 3, null);
                $filter->token = $keyParts[1] ?: $keyParts[0];
                $filter->optionToken = $keyParts[2];

                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @param string $searchPhrase
     * @return Model\Product\RequestFilter
     */
    public function getRequestObjectBySearchPhrase($searchPhrase) {
        $filter = new Model\Product\RequestFilter();
        $filter->token = 'q';
        $filter->name = 'q';
        $filter->value = $searchPhrase;

        return $filter;
    }

    /**
     * Возвращает фильтр из http-запроса, который относится к категории товара
     *
     * @param Model\Product\RequestFilter[] $filters
     * @return Model\Product\RequestFilter
     */
    public function getCategoryRequestObjectByRequestList($filters) {
        $return = null;

        foreach ($filters as $filter) {
            if ('category' == $filter->token) {
                $return = $filter;
                break;
            }
        }

        return $return;
    }

    /**
     * Возвращает фильтр из http-запроса, который относится к срезу товаров
     *
     * @param Model\Product\RequestFilter[] $filters
     * @return Model\Product\RequestFilter
     */
    public function getSliceRequestObjectByRequestList($filters) {
        $return = null;

        foreach ($filters as $filter) {
            if ('slice' == $filter->token) {
                $return = $filter;
                break;
            }
        }

        return $return;
    }

    /**
     * @param Model\Product\Slice $slice
     * @return Model\Product\RequestFilter
     */
    public function getSliceRequestObjectBySlice(Model\Product\Slice $slice) {
        $filter = new Model\Product\RequestFilter();
        $filter->token = 'slice';
        $filter->name = 'slice';
        $filter->value = $slice->token;

        return $filter;
    }

    /**
     * @param Model\Brand $brand
     * @return Model\Product\RequestFilter
     */
    public function getBrandRequestObjectByBrand(Model\Brand $brand) {
        $filter = new Model\Product\RequestFilter();
        $filter->token = 'brand';
        $filter->name = 'f-brand-' . $brand->token;
        $filter->value = $brand->id;

        return $filter;
    }

    /**
     * @param Model\Product\RequestFilter[] $requestFilters
     * @return array
     */
    public function dumpRequestObjectList(array $requestFilters) {
        $return = [];

        // TODO: перевести все на f-
        $filterData = [];
        foreach ($requestFilters as $requestFilter) {
            $key = $requestFilter->name;
            $value = $requestFilter->value;

            if (0 === strpos($key, 'f-')) {
                $parts = array_pad(explode('-', $key), 3, null);

                if (!isset($filterData[$parts[1]])) {
                    $filterData[$parts[1]] = [
                        'value' => [],
                    ];
                }

                if (('from' == $parts[2]) || ('to' == $parts[2])) {
                    $filterData[$parts[1]]['value'][$parts[2]] = $value;
                } else {
                    $filterData[$parts[1]]['value'][] = $value;
                }
            } else if (0 === strpos($key, 'tag-')) {
                if (!isset($filterData['tag'])) {
                    $filterData['tag'] = [
                        'value' => [],
                    ];
                }

                $filterData['tag']['value'][] = $value;
            } else if (in_array($key, ['category', 'shop', 'q'])) {
                if (!isset($filterData[$key])) {
                    $filterData[$key] = [
                        'value' => [],
                    ];
                }
                $filterData[$key]['value'][] = $value;
            }
        }

        foreach ($filterData as $key => $filter) {
            if (isset($filter['value']['from']) || isset($filter['value']['to'])) {
                $return[] = [$key, 2, isset($filter['value']['from']) ? $filter['value']['from'] : null, isset($filter['value']['to']) ? $filter['value']['to'] : null];
            } else if ('q' == $key) {
                $return[] = ['text', 3, reset($filter['value'])];
            } else {
                $return[] = [$key, 1, $filter['value']];
            }
        }

        return $return;
    }
}