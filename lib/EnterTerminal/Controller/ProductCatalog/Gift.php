<?php

namespace EnterTerminal\Controller\ProductCatalog {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
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

            // фильтры в http-запросе и настройках среза
            $baseRequestFilters = [];
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

            // захардкоженные фильтры
            /** @var Model\Product\Filter[] $filters */
            $filters = [];

            foreach ($controllerResponse->filters as $filter) {
                if ('price' === $filter->token) {
                    $filters[] = $filter;
                    break;
                }
            }

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
            $response->category = $controllerResponse->category;
            $response->categories = $controllerResponse->category ? $controllerResponse->category->children : $controllerResponse->categories;
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->products = $controllerResponse->products;
            $response->productCount = $controllerResponse->productUiPager->count;
            $response->filters = $filters;
            $response->filterGroups = $filterGroups;
            $response->sortings = $controllerResponse->sortings;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ProductCatalog\Gift {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Category */
        public $category;
        /** @var Model\Product\Category[] */
        public $categories = [];
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
        /** @var Model\Product\Filter\Group[] */
        public $filterGroups = [];
    }
}