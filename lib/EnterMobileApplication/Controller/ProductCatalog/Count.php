<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Count {
    use ConfigTrait;
    use Controller\ProductListingTrait;
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

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

        if (strpos($categoryId, (new \EnterMobileApplication\Repository\MainMenu())->getSecretSaleElement()->id) === 0) {
            return $this->getResponseForSecretSale($request);
        }

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

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    private function getResponseForSecretSale(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);

        $promoUi = null;
        $categoryId = null;
        call_user_func(function() use(&$promoUi, &$categoryId, $request) {
            $ids = explode(':', trim((string)$request->query['categoryId']));
            if (isset($ids[1])) {
                $promoUi = $ids[1];
            }

            if (isset($ids[2])) {
                $categoryId = $ids[2];
            }
        });

        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        if (!$promoUi) {
            throw new \Exception('Не указан promo ui в параметре categoryId', Http\Response::STATUS_BAD_REQUEST);
        }

        $regionItemQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionItemQuery);

        $secretSalePromoItemQuery = new \EnterQuery\Promo\SecretSale\GetItemByUi($promoUi);
        $curl->prepare($secretSalePromoItemQuery);

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionItemQuery);

        /** @var Model\SecretSalePromo|null $secretSalePromo */
        $secretSalePromo = $secretSalePromoItemQuery->getResult();
        if ($secretSalePromo) {
            $secretSalePromo = new Model\SecretSalePromo($secretSalePromo);
            $time = time();
            if ($secretSalePromo->startAt > $time || $secretSalePromo->endAt < $time) {
                $secretSalePromo = null;
            }
        }

        $productCount = 0;
        if ($secretSalePromo && $secretSalePromo->products) {
            $productListQueries = [];
            $productDescriptionListQueries = [];
            foreach (array_chunk(array_map(function(Model\Product $product) { return $product->ui; }, $secretSalePromo->products), $config->curl->queryChunkSize) as $uisInChunk) {
                $productListQuery = new Query\Product\GetListByUiList($uisInChunk, $region->id, ['related' => false]);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByUiList($uisInChunk, [
                    'media'       => true,
                    'media_types' => ['main'],
                    'category'    => true,
                    'label'       => true,
                    'brand'       => true,
                    'tag'         => true,
                    'model'       => true,
                ]);

                $curl->prepare($productListQuery);
                $curl->prepare($productDescriptionListQuery);

                $productListQueries[] = $productListQuery;
                $productDescriptionListQueries[] = $productDescriptionListQuery;
            }

            $curl->execute();

            $secretSalePromo->products = $productRepository->getIndexedObjectListByQueryList($productListQueries, $productDescriptionListQueries);
            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

            (new \EnterRepository\Product\Category())->filterSecretSaleProducts($secretSalePromo->products, $categoryId, $requestFilters);

            $productCount = count($secretSalePromo->products);
        }

        return new Http\JsonResponse([
            'count' => $productCount,
        ]);
    }
}