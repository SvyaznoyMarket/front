<?php

namespace EnterMobileApplication\Controller\ProductCatalog {

    use Enter\Http;
    use EnterAggregator\Model\Context;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\ProductCatalog\Category\Response;

    class Category {
        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\Response
         */
        public function execute(Http\Request $request) {
            $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId');
            }

            // ид категории
            $categoryId = trim((string)$request->query['categoryId']);
            if (!$categoryId) {
                throw new \Exception('Не указан параметр categoryId');
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

            // фильтры в запросе
            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
            // фильтр категории в http-запросе
            //$requestFilters[] = $filterRepository->getRequestObjectByCategory($category);

            $context = new Context\ProductCatalog();
            $context->mainMenu = false;
            $context->parentCategory = false;
            $controllerResponse = (new \EnterAggregator\Controller\ProductList())->execute(
                $regionId,
                ['id' => $categoryId], // критерий получения категории товара
                $pageNum, // номер страницы
                $limit, // лимит
                $sorting, // сортировка
                $filterRepository, // репозиторий фильтров
                [],
                $requestFilters, // фильтры в http-запросе
                $context
            );

            // категория
            if (!$controllerResponse->category) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
            }

            // ответ
            $response = new Response();
            $response->category = $controllerResponse->category;
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productIdPager->count;
            $response->filters = $controllerResponse->filters;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductCatalog\Category {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Category */
        public $category;
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