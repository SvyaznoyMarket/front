<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Slice {
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

        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

        // ид региона
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // токен среза
        $sliceToken = trim((string)$request->query['sliceId']);
        if (!$sliceToken) {
            throw new \Exception('Не указан параметр sliceId', Http\Response::STATUS_BAD_REQUEST);
        }

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

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

        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\ProductList();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->mainMenu = false;
        $controllerRequest->config->parentCategory = false;
        $controllerRequest->config->branchCategory = false;
        $controllerRequest->config->favourite = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->categoryCriteria = $categoryId ? ['id' => $categoryId] : []; // критерий получения категории товара
        $controllerRequest->pageNum = $pageNum;
        $controllerRequest->limit = $limit;
        $controllerRequest->sorting = $sorting;
        $controllerRequest->filterRepository = $filterRepository;
        $controllerRequest->baseRequestFilters = $baseRequestFilters;
        $controllerRequest->requestFilters = $requestFilters;
        $controllerRequest->userToken = $userAuthToken;
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        // категория
        if ($categoryId && !$controllerResponse->category) {
            if ($config->region->defaultId != $regionId) {
                $categoryItemQuery = new Query\Product\Category\GetItemById($categoryId, $config->region->defaultId);
                $curl->prepare($categoryItemQuery)->execute();

                if ($categoryItemQuery->getResult()) {
                    return (new Controller\Error\NotFoundInRegion())->execute($request, 'Нет товаров в вашем регионе');
                }
            }

            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
        }

        // ответ
        $response = [
            'slice'        => [
                'token' => $slice->token,
                'name'  => $slice->name,
            ],
            'category'     => $controllerResponse->category ? [
                'id'           => $controllerResponse->category->id,
                'name'         => $controllerResponse->category->name,
                'media'        => $controllerResponse->category->media,
                'productCount' => $controllerResponse->category->productCount,
                'hasChildren'  => $controllerResponse->category->hasChildren,
            ] : null,
            'categories'   => array_map(function(Model\Product\Category $category) {
                return [
                    'id'           => $category->id,
                    'name'         => $category->name,
                    'media'        => $category->media,
                    'productCount' => $category->productCount,
                    'hasChildren'  => $category->hasChildren,
                ];
            }, $controllerResponse->category ? $controllerResponse->category->children : $controllerResponse->categories),
        ];
        if ($controllerResponse->productUiPager) {
            $response['productCount'] = $controllerResponse->productUiPager->count;
            $response['products'] = $this->getProductList($controllerResponse->products);
            $response['filters'] = $this->getFilterList($controllerResponse->filters);
            $response['sortings'] = $this->getSortingList($controllerResponse->sortings);
        }

        return new Http\JsonResponse($response);
    }
}