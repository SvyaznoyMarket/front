<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\Model\Context;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Count {
    use Controller\ProductListingTrait;
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

        $userToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

        // ид региона
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // фильтры в http-запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

        // токен среза
        $sliceToken = trim((string)$request->query['sliceId']);

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);

        // запрос среза
        $sliceItemQuery = null;
        if ($sliceToken) {
            $sliceItemQuery = new Query\Product\Slice\GetItemByToken($sliceToken);
            $curl->prepare($sliceItemQuery);
        }

        // запрос категории
        $categoryItemQuery = null;
        if ($categoryId) {
            //$categoryItemQuery = new Query\Product\Category\GetItemById($categoryId, $regionId);
            //$curl->prepare($categoryItemQuery);
        }

        $curl->execute();

        // срез
        $slice = null;
        if ($sliceItemQuery) {
            $slice = (new \EnterRepository\Product\Slice())->getObjectByQuery($sliceItemQuery);
            if (!$slice) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Срез товаров @%s не найден', $sliceToken));
            }
        }

        $baseRequestFilters = [];
        if ($slice) {
            // фильтры в http-запросе и настройках среза
            $baseRequestFilters = (new \EnterMobile\Repository\Product\Filter())->getRequestObjectListByHttpRequest(new Http\Request($slice->filters)); // FIXME !!!
            // AG-43: если выбрана категория, то удялять замороженные фильтры-категории
            if ($categoryId) {
                foreach ($baseRequestFilters as $i => $baseRequestFilter) {
                    if ('category' == $baseRequestFilter->token) {
                        unset($baseRequestFilters[$i]);
                    }
                }
            }
        }

        if ($categoryId) {
            $filter = new Model\Product\RequestFilter();
            $filter->token = 'category';
            $filter->name = 'category';
            $filter->value = $categoryId;
            $baseRequestFilters[] = $filter;
        }

        // запрос листинга идентификаторов товаров
        $productUiPagerQuery = new Query\Product\GetUiPager(
            $filterRepository->dumpRequestObjectList(array_merge($baseRequestFilters, $requestFilters)),
            null,
            $regionId,
            0,
            0
        );
        $curl->prepare($productUiPagerQuery);

        $curl->execute();

        // ответ
        $response = [
            'count' => $productUiPagerQuery->getResult()['count'],
        ];

        return new Http\JsonResponse($response);
    }
}