<?php

namespace EnterTerminal\Controller\ProductCatalog {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
    use EnterTerminal\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\ProductCatalog\Category\Response;

    class Category {
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
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // ид категории
            $categoryId = trim((string)$request->query['categoryId']);
            if (!$categoryId) {
                throw new \Exception('Не указан параметр categoryId', Http\Response::STATUS_BAD_REQUEST);
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

            // контроллер
            $controller = new \EnterAggregator\Controller\ProductList();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->config->mainMenu = false;
            $controllerRequest->config->parentCategory = true;
            $controllerRequest->config->branchCategory = false;
            $controllerRequest->config->shopState = true;
            $controllerRequest->regionId = $regionId;
            $controllerRequest->categoryCriteria = ['id' => $categoryId]; // критерий получения категории товара
            $controllerRequest->pageNum = $pageNum;
            $controllerRequest->limit = $limit;
            $controllerRequest->sorting = $sorting;
            $controllerRequest->filterRepository = $filterRepository;
            $controllerRequest->baseRequestFilters = [];
            $controllerRequest->requestFilters = $requestFilters;
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // категория
            if (!$controllerResponse->category) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
            }

            // ответ
            $response = new Response();
            $response->category = $controllerResponse->category;
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productUiPager->count;
            $response->filters = $controllerResponse->filters;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ProductCatalog\Category {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Category */
        public $category;
        /** @var Model\Product\Category\Config */
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