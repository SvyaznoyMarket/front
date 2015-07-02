<?php

namespace EnterTerminal\Controller\ProductCatalog {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\ProductCatalog\Gift\Response;

    class Gift {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\Response
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $filterRepository = new \EnterTerminal\Repository\Product\Filter();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
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

            // базовые фильтры
            $baseRequestFilters = [];

            // фильтры в запросе
            $requestFilters = $this->getRequestFilters($request, $filterRepository);

            // контроллер
            $controller = new \EnterAggregator\Controller\ProductList();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->config->mainMenu = false;
            $controllerRequest->config->parentCategory = true;
            $controllerRequest->config->branchCategory = false;
            $controllerRequest->config->shopState = true;
            $controllerRequest->regionId = $regionId;
            $controllerRequest->categoryCriteria = $categoryId ? ['id' => $categoryId] : []; // критерий получения категории товара
            $controllerRequest->pageNum = $pageNum;
            $controllerRequest->limit = $limit;
            $controllerRequest->sorting = $sorting;
            $controllerRequest->filterRepository = $filterRepository;
            $controllerRequest->baseRequestFilters = $baseRequestFilters;
            $controllerRequest->requestFilters = $requestFilters;
            $controllerRequest->filterRequestFilters = $this->getFilterRequestFilters($requestFilters);
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // категория
            if ($categoryId && !$controllerResponse->category) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
            }

            // исключение фильтров
            $deletedFilterTokens = []; // ид фильтров, которые будут удалены
            foreach ($requestFilters as $requestFilter) {
                if ('tag-sex' === $requestFilter->token) {
                    if ('687' == $requestFilter->value) { // если подарок Женщине, то Любимой
                        $deletedFilterTokens[] = 'tag-relation-man';
                    } else if ('688' == $requestFilter->value) {
                        $deletedFilterTokens[] = 'tag-relation-woman';
                    }
                }
            }

            // обработка дынных из json-файла
            $filterData = (array)json_decode(file_get_contents($config->dir . '/data/query/product-catalog-gift/listing-filter.json'), true) + [
                'filter_groups' => [],
                'filters'       => [],
            ];
            // группы фильтров
            /** @var Model\Product\Filter\Group[] $filterGroups */
            $filterGroups = [];
            foreach ($filterData['filter_groups'] as $item) {
                if (!isset($item['id'])) continue;

                $filterGroups[] = new Model\Product\Filter\Group($item);
            }

            // фильтры
            /** @var Model\Product\Filter[] $filters */
            $filters = [];

            // фильтр по цене
            foreach ($controllerResponse->filters as $filter) {
                if ('price' === $filter->token) {
                    $filters[] = $filter;
                    break;
                }
            }

            // фильтры по категориям
            $filters = array_merge($filters, $filterRepository->getObjectListByCategoryList($controllerResponse->categories));

            // захардкоженные фильтры
            foreach ($filterData['filters'] as $item) {
                if (!isset($item['filter_id'])) continue;

                $filter = new Model\Product\Filter($item);
                $filter->isMultiple = false;
                if (in_array($filter->token, $deletedFilterTokens)) continue;

                $filters[] = $filter;
            }
            $filterRepository->setValueForObjectList($filters, $requestFilters);

            // ответ
            $response = new Response();
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productUiPager->count;
            $response->filters = $filters;
            $response->filterGroups = $filterGroups;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }

        /**
         * @return Model\Product\RequestFilter[]
         */
        private function getRequestFilters(Http\Request $request, \EnterTerminal\Repository\Product\Filter $filterRepository) {
            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
            $isSubmitted = (bool)$requestFilters;

            if (!$filterRepository->getObjectByToken($requestFilters, 'tag-holiday')) {
                $specialPageListQuery = new Query\SpecialPage\GetListByTokenList(['gift']);
                $this->getCurl()->query($specialPageListQuery);

                $requestFilters[] = $this->createRequestFilter('tag-holiday', 0, !empty($specialPageListQuery->getResult()['special_pages']['gift']['default_filter_id']) ? (string)$specialPageListQuery->getResult()['special_pages']['gift']['default_filter_id'] : '737');
            }

            if (!$filterRepository->getObjectByToken($requestFilters, 'tag-sex')) {
                $holidayFilter = $filterRepository->getObjectByToken($requestFilters, 'tag-holiday');

                if ($holidayFilter && ($holidayFilter->value == 738 || $holidayFilter->value == 707)) {
                    $requestFilters[] = $this->createRequestFilter('tag-sex', 0, '688');
                } else {
                    $requestFilters[] = $this->createRequestFilter('tag-sex', 0, '687');
                }
            }

            $sexFilter = $filterRepository->getObjectByToken($requestFilters, 'tag-sex');
            if ($sexFilter && $sexFilter->value == 687) {
                if (!$filterRepository->getObjectByToken($requestFilters, 'tag-relation-woman')) {
                    $requestFilters[] = $this->createRequestFilter('tag-relation-woman', 0, '689');
                }

                $filterRepository->deleteObjectByToken($requestFilters, 'tag-relation-man');
            } else {
                if (!$filterRepository->getObjectByToken($requestFilters, 'tag-relation-man')) {
                    $requestFilters[] = $this->createRequestFilter('tag-relation-man', 0, '698');
                }

                $filterRepository->deleteObjectByToken($requestFilters, 'tag-relation-woman');
            }

            if (!$filterRepository->getObjectByToken($requestFilters, 'tag-age')) {
                $requestFilters[] = $this->createRequestFilter('tag-age', 0, '724');
            }

            $holidayFilter = $filterRepository->getObjectByToken($requestFilters, 'tag-holiday');
            $sexFilter = $filterRepository->getObjectByToken($requestFilters, 'tag-sex');
            $relationWomanFilter = $filterRepository->getObjectByToken($requestFilters, 'tag-relation-woman');
            if (!$filterRepository->getObjectByToken($requestFilters, 'category') && !$isSubmitted && ($holidayFilter && $holidayFilter->value == 737) && ($sexFilter && $sexFilter->value == 687) && ($relationWomanFilter && $relationWomanFilter->value == 689)) {
                $requestFilters[] = $this->createRequestFilter('category', 0, '923');
                $requestFilters[] = $this->createRequestFilter('category', 1, '2545');
            }

            return $requestFilters;
        }

        private function createRequestFilter($token, $optionIndex, $optionValue) {
            $filter = new Model\Product\RequestFilter();
            $filter->token = $token;
            $filter->name = $token;
            $filter->optionToken = $optionIndex;
            $filter->value = $optionValue;
            return $filter;
        }

        /**
         * @param Model\Product\RequestFilter[] $filters
         * @return Model\Product\RequestFilter[]
         */
        private function getFilterRequestFilters(array $filters) {
            foreach ($filters as $key => $filter) {
                // Поскольку метод /v2/listing/filter самостоятельно не исключает фильтр по цене из рассчёта min/max значений цены, делаем это сами
                if ('price' === $filter->token) {
                    unset($filters[$key]);
                }
            }

            return array_values($filters);
        }
    }
}

namespace EnterTerminal\Controller\ProductCatalog\Gift {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
        /** @var Model\Product\Sorting[] */
        public $sortings = [];
        /** @var Model\Product\Filter[] */
        public $filters = [];
        /** @var Model\Product\Filter\Group[] */
        public $filterGroups = [];
    }
}