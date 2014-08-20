<?php

namespace EnterMobileApplication\Controller\ProductCatalog {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\ProductCatalog\Slice\Response;

    class Slice {
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
                throw new \Exception('Не указан параметр regionId');
            }

            // токен среза
            $sliceToken = trim((string)$request->query['sliceId']);
            if (!$sliceToken) {
                throw new \Exception('Не указан параметр sliceId');
            }

            // ид категории
            $categoryId = trim((string)$request->query['categoryId']);

            // номер страницы
            $pageNum = (int)$request->query['page'] ?: 1;

            $limit = (int)$request->query['limit'];
            if ($limit < 1) {
                throw new \Exception('limit не должен быть меньше 1');
            }
            if ($limit > 40) {
                throw new \Exception('limit не должен быть больше 40');
            }

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
            $baseRequestFilters = $filterRepository->getRequestObjectListByHttpRequest(new Http\Request($slice->filters));

            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

            $context = new Context\ProductCatalog();
            $context->mainMenu = false;
            $context->parentCategory = false;
            $context->branchCategory = false;
            $context->productOnlyForLeafCategory = true;
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
            $categories = [];
            if ($controllerResponse->category) {
                $categories = $controllerResponse->category->children;
            } else {
                $categoryListQuery = new Query\Product\Category\GetTreeList($controllerResponse->region->id, null, $filterRepository->dumpRequestObjectList($baseRequestFilters));
                $curl->prepare($categoryListQuery)->execute();

                try {
                    $categories = (new \EnterRepository\Product\Category())->getObjectListByQuery($categoryListQuery);
                } catch(\Exception $e) {
                    // TODO
                }
            }

            if (count($categories) <= 1) {
                $categories = [];
            }

            // ответ
            $response = new Response();
            $response->slice = $slice;
            $response->category = $controllerResponse->category;
            $response->categories = $categories;
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productIdPager ? $controllerResponse->productIdPager->count : null;
            $response->filters = $controllerResponse->filters;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductCatalog\Slice {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Slice */
        public $slice;
        /** @var Model\Product\Category|null */
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