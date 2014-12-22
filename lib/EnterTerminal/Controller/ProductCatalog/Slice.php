<?php

namespace EnterTerminal\Controller\ProductCatalog {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
    use EnterTerminal\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\ProductCatalog\Slice\Response;

    class Slice {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\Response
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $filterRepository = new \EnterTerminal\Repository\Product\Filter();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // ид среза товаров
            $sliceToken = trim((string)$request->query['sliceId']);
            if (!$sliceToken) {
                throw new \Exception('Не указан параметр sliceId');
            }

            // ид категории
            $categoryId = trim((string)$request->query['categoryId']);
            if (!$categoryId) {
                $categoryId = null;
            }

            // номер страницы
            $pageNum = (int)$request->query['page'] ?: 1;

            // количество товаров на страницу
            $limit = (int)$request->query['limit'] ?: 10;

            // сортировка
            $sorting = null;
            if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
                $sorting = new Model\Product\Sorting();
                $sorting->token = trim((string)$request->query['sort']['token']);
                $sorting->direction = trim((string)$request->query['sort']['direction']);
            }

            // запрос среза
            $sliceItemQuery = new Query\Product\Slice\GetItemByToken($sliceToken);
            $curl->prepare($sliceItemQuery);

            $curl->execute();

            // срез
            $slice = (new \EnterRepository\Product\Slice())->getObjectByQuery($sliceItemQuery);
            if (!$slice) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Срез товаров @%s не найден', $sliceToken));
            }

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

            // фильтры в запросе
            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

            $context = new Context\ProductCatalog();
            $context->mainMenu = false;
            $context->parentCategory = true;
            $context->branchCategory = false;
            $context->shopState = true;
            $context->isSlice = true;
            $controllerResponse = (new \EnterAggregator\Controller\ProductList())->execute(
                $regionId,
                $categoryId ? ['id' => $categoryId] : [], // критерий получения категории товара
                $pageNum, // номер страницы
                $limit, // лимит
                $sorting, // сортировка
                $filterRepository, // репозиторий фильтров
                $baseRequestFilters,
                $requestFilters, // фильтры в http-запросе
                $context
            );

            // категория
            if ($categoryId && !$controllerResponse->category) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
            }

            // список категорий
            // FIXME !!!
            $baseRequestFilters = (new \EnterMobile\Repository\Product\Filter())->getRequestObjectListByHttpRequest(new Http\Request($slice->filters)); // FIXME !!!
            $categoryListQuery = new Query\Product\Category\GetTreeList(
                $controllerResponse->region->id,
                null,
                $filterRepository->dumpRequestObjectList($baseRequestFilters),
                $controllerResponse->category ? $controllerResponse->category->id : null
            );
            $curl->prepare($categoryListQuery)->execute();

            /** @var Model\Product\Category[] $categories */
            $categories = [];
            try {
                $categoryListResult = $categoryListQuery->getResult();
                if (isset($categoryListResult[0]['children'][0])) {
                    foreach ($categoryListResult[0]['children'] as $categoryItem) {
                        if (!isset($categoryItem['uid'])) continue;

                        $categories[] = new Model\Product\Category($categoryItem);
                    }
                }
            } catch(\Exception $e) {
                // TODO
            }

            // ответ
            $response = new Response();
            $response->slice = $slice;
            $response->category = $controllerResponse->category;
            $response->categories = $categories;
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productUiPager->count;
            $response->filters = $controllerResponse->filters;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ProductCatalog\Slice {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Slice */
        public $slice;
        /** @var Model\Product\Category */
        public $category;
        /** @var Model\Product\Category[] */
        public $categories = [];
        /** @var Model\Product\Catalog\Config */
        public $catalogConfig;
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
        /** @var Model\Product\Sorting[] */
        public $sortings = [];
        /** @var Model\Product\Filter[] */
        public $filters = [];
    }
}