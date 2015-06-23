<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;
use EnterModel as Model;

class Category {
    use ConfigTrait, CurlTrait;
    use Controller\ProductListingTrait;

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

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);
        if (!$categoryId) {
            throw new \Exception('Не указан параметр categoryId', Http\Response::STATUS_BAD_REQUEST);
        }

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

        // количество товаров на страницу
        $limit = (int)$request->query['limit'];
        if ($limit < 1) {
            throw new \Exception('limit не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
        }
        if ($limit > 40) {
            throw new \Exception('limit не должен быть больше 40', Http\Response::STATUS_BAD_REQUEST);
        }

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
        $controllerRequest->config->parentCategory = false;
        $controllerRequest->config->branchCategory = false;
        // MAPI-43
        $controllerRequest->config->loadProductsForRootCategory = false;
        $controllerRequest->config->loadFiltersForRootCategory = false;
        $controllerRequest->config->loadSortingsForRootCategory = false;
        $controllerRequest->config->loadFiltersForMiddleCategory = false;
        $controllerRequest->config->loadSortingsForMiddleCategory = false;
        $controllerRequest->config->favourite = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->categoryCriteria = ['id' => $categoryId]; // критерий получения категории товара
        $controllerRequest->pageNum = $pageNum;
        $controllerRequest->limit = $limit;
        $controllerRequest->sorting = $sorting;
        $controllerRequest->filterRepository = $filterRepository;
        $controllerRequest->baseRequestFilters = [];
        $controllerRequest->requestFilters = $requestFilters;
        $controllerRequest->userToken = $userToken;
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        // категория
        if (!$controllerResponse->category) {
            if ($config->region->defaultId != $regionId) {
                $categoryItemQuery = new Query\Product\Category\GetItemById($categoryId, $config->region->defaultId);
                $curl->prepare($categoryItemQuery)->execute();

                if ($categoryItemQuery->getResult()) {
                    return (new Controller\Error\NotFoundInRegion())->execute($request, 'Нет товаров в вашем регионе');
                }
            }

            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
        }

        $response = [
            'category' => $this->getResponseForCategory($controllerResponse->category),
            'productCount' => $controllerResponse->productUiPager ? $controllerResponse->productUiPager->count : null,
            'products' => $this->getProductList($controllerResponse->products),
            'filters' => $this->getFilterList($controllerResponse->filters),
            'sortings' => $this->getSortingList($controllerResponse->sortings),
        ];
        
        return new Http\JsonResponse($response);
    }

    /**
     * @return array
     */
    private function getResponseForCategory(Model\Product\Category $category) {
        $maxLevel = $category->level + 1;
        $walkByCategory = function(\EnterModel\Product\Category $category) use (&$walkByCategory, &$maxLevel) {
            $response = [
                'id'          => $category->id,
                'name'        => $category->name,
                'media'       => $category->media,
                'hasChildren' => $category->hasChildren,
            ];

            if (($category->level < $maxLevel) && $category->children) {
                $response['children'] = [];
                foreach ($category->children as $child) {
                    $response['children'][] = $walkByCategory($child);
                }
            }

            return $response;
        };
        
        return $walkByCategory($category);
    }
}
